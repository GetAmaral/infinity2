<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Organization;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Service for importing users from XLSX files
 */
final class UserImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator
    ) {
    }

    /**
     * Parse XLSX file and return structured data
     *
     * @return array{users: array<int, array>, errors: array<int, array>}
     */
    public function parseXlsx(string $filePath, ?Organization $organization): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $users = [];
        $errors = [];

        // Read header row to map columns
        $headerRow = $sheet->rangeToArray('A1:Z1', null, true, true, true)[1];
        $columnMap = $this->mapColumns($headerRow);

        // Start from row 2 (skip header)
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, true, true)[$row];

            // Extract user data first - pass sheet and row number to handle hyperlinks
            $userData = $this->extractUserData($rowData, $columnMap, $sheet, $row);

            // CRITICAL: Skip row if email (column A) is empty - this ignores instruction rows and empty rows
            if (empty($userData['email'])) {
                continue;
            }

            // Now validate the row data
            $rowErrors = $this->validateUserData($userData, $organization, $row);

            if (!empty($rowErrors)) {
                $errors[$row] = [
                    'row' => $row,
                    'data' => $userData,
                    'errors' => $rowErrors,
                ];
            } else {
                $users[] = [
                    'row' => $row,
                    'data' => $userData,
                ];
            }
        }

        return [
            'users' => $users,
            'errors' => $errors,
        ];
    }

    /**
     * Import users into database
     *
     * @param array<int, array> $users
     */
    public function importUsers(
        array $users,
        Organization $organization,
        UserInterface $currentUser
    ): array {
        $imported = [];
        $failed = [];

        foreach ($users as $userData) {
            try {
                $user = $this->createUser($userData['data'], $organization);

                $this->entityManager->persist($user);
                $this->entityManager->flush();

                $imported[] = [
                    'row' => $userData['row'],
                    'email' => $user->getEmail(),
                    'name' => $user->getName(),
                ];
            } catch (\Exception $e) {
                $failed[] = [
                    'row' => $userData['row'],
                    'email' => $userData['data']['email'] ?? 'N/A',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
        ];
    }

    /**
     * Map column headers to field names
     */
    private function mapColumns(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $index => $header) {
            $normalized = strtolower(trim((string)$header));

            $map[$index] = match ($normalized) {
                'email', 'e-mail' => 'email',
                'name', 'full name', 'nome' => 'name',
                'password', 'senha' => 'password',
                'roles', 'role', 'perfil', 'perfis' => 'roles',
                'openai api key', 'openai_api_key', 'api key' => 'openAiApiKey',
                default => null,
            };
        }

        return $map;
    }

    /**
     * Extract user data from row
     * Handles Excel hyperlinks in email field (mailto: links)
     */
    private function extractUserData(array $rowData, array $columnMap, Worksheet $sheet, int $row): array
    {
        $userData = [
            'email' => null,
            'name' => null,
            'password' => null,
            'roles' => [],
            'openAiApiKey' => null,
        ];

        foreach ($rowData as $index => $value) {
            $fieldName = $columnMap[$index] ?? null;

            if ($fieldName === null) {
                continue;
            }

            // Special handling for email field - check if it's a hyperlink
            if ($fieldName === 'email') {
                $columnLetter = $this->getColumnLetter($index);
                $cellCoordinate = $columnLetter . $row;
                $cell = $sheet->getCell($cellCoordinate);

                // Check if cell has a hyperlink
                if ($cell->hasHyperlink()) {
                    $hyperlink = $cell->getHyperlink();
                    $url = $hyperlink->getUrl();

                    // Extract email from mailto: link
                    if (str_starts_with(strtolower($url), 'mailto:')) {
                        $email = substr($url, 7); // Remove "mailto:" prefix
                        // Remove any query parameters (e.g., ?subject=...)
                        $email = explode('?', $email)[0];
                        $userData['email'] = trim($email);
                        continue;
                    }
                }

                // If not a hyperlink or not a mailto link, use the cell value
                $userData['email'] = !empty(trim((string)$value)) ? trim((string)$value) : null;
                continue;
            }

            // Handle other fields normally
            $value = trim((string)$value);

            if ($fieldName === 'roles') {
                // Split comma-separated roles
                $userData['roles'] = array_filter(
                    array_map('trim', explode(',', $value)),
                    fn($role) => !empty($role)
                );
            } else {
                $userData[$fieldName] = !empty($value) ? $value : null;
            }
        }

        return $userData;
    }

    /**
     * Convert column index to Excel column letter (1 -> A, 2 -> B, etc.)
     */
    private function getColumnLetter(int $index): string
    {
        // PhpSpreadsheet uses 1-based indexing for columns
        // But our array index might be different depending on rangeToArray
        // For safety, let's use the built-in method
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
    }

    /**
     * Validate user data
     * CRITICAL: Validate email FIRST, then only validate other fields if email is valid
     */
    private function validateUserData(array $userData, ?Organization $organization, int $row): array
    {
        $errors = [];

        // STEP 1: Validate email FIRST (this is the primary identifier)
        if (empty($userData['email'])) {
            // This should not happen as we skip empty emails in parseXlsx, but keep as safety
            $errors[] = 'Email is required';
            return $errors; // Stop here - no point validating other fields
        }

        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
            return $errors; // Stop here - invalid email means row is bad
        }

        // Check if email already exists
        $existingUser = $this->userRepository->findOneBy(['email' => $userData['email']]);
        if ($existingUser !== null) {
            $errors[] = "Email already exists: {$userData['email']}";
            return $errors; // Stop here - duplicate email
        }

        // STEP 2: Email is valid - now validate other required fields

        // Validate name (required)
        if (empty($userData['name'])) {
            $errors[] = 'Name is required';
        } elseif (strlen($userData['name']) < 2) {
            $errors[] = 'Name must be at least 2 characters';
        } elseif (strlen($userData['name']) > 255) {
            $errors[] = 'Name must not exceed 255 characters';
        }

        // Validate password (required)
        if (empty($userData['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($userData['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }

        // STEP 3: Validate optional fields

        // Validate roles (optional but must exist if provided)
        if (!empty($userData['roles'])) {
            foreach ($userData['roles'] as $roleName) {
                $role = $this->roleRepository->findOneBy(['name' => $roleName]);
                if ($role === null) {
                    $errors[] = "Role not found: {$roleName}";
                }
            }
        }

        // Validate organization
        if ($organization === null) {
            $errors[] = 'No organization context available';
        }

        return $errors;
    }

    /**
     * Create User entity from validated data
     */
    private function createUser(array $userData, Organization $organization): User
    {
        $user = new User();
        $user->setEmail($userData['email']);
        $user->setName($userData['name']);
        $user->setOrganization($organization);

        // Hash password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
        $user->setPassword($hashedPassword);

        // Set as verified (per requirements)
        $user->setIsVerified(true);

        // Terms always false (per requirements)
        $user->setTermsSigned(false);

        // Set OpenAI API Key if provided
        if (!empty($userData['openAiApiKey'])) {
            $user->setOpenAiApiKey($userData['openAiApiKey']);
        }

        // Assign roles if provided
        if (!empty($userData['roles'])) {
            foreach ($userData['roles'] as $roleName) {
                $role = $this->roleRepository->findOneBy(['name' => $roleName]);
                if ($role !== null) {
                    $user->addRoleEntity($role);
                }
            }
        }

        return $user;
    }

    /**
     * Check if row is completely empty
     */
    private function isRowEmpty(array $rowData): bool
    {
        foreach ($rowData as $cell) {
            if (!empty(trim((string)$cell))) {
                return false;
            }
        }
        return true;
    }
}
