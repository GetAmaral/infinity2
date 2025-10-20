<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use App\Service\UserImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-user-import',
    description: 'Test user import from XLSX file',
)]
final class TestUserImportCommand extends Command
{
    public function __construct(
        private readonly UserImportService $userImportService,
        private readonly OrganizationRepository $organizationRepository,
        private readonly UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('file', InputArgument::REQUIRED, 'Path to XLSX file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $filePath = $input->getArgument('file');

        if (!file_exists($filePath)) {
            $io->error("File not found: $filePath");
            return Command::FAILURE;
        }

        // Get first organization
        $organization = $this->organizationRepository->findAll()[0] ?? null;

        if (!$organization) {
            $io->error('No organization found in database!');
            return Command::FAILURE;
        }

        $io->info("Using Organization: " . $organization->getName() . " (ID: " . $organization->getId()->toString() . ")");
        $io->newLine();

        try {
            // Parse the XLSX file
            $io->section('STEP 1: Parsing XLSX file');
            $result = $this->userImportService->parseXlsx($filePath, $organization);

            $io->success('Parse completed!');
            $io->newLine();

            // Display valid users
            $io->section('VALID USERS: ' . count($result['users']));
            foreach ($result['users'] as $i => $user) {
                $io->writeln("User #" . ($i + 1) . " (Row " . $user['row'] . "):");
                $io->writeln("  Email: " . ($user['data']['email'] ?? 'N/A'));
                $io->writeln("  Name: " . ($user['data']['name'] ?? 'N/A'));
                $io->writeln("  Password: " . (isset($user['data']['password']) ? str_repeat('*', strlen($user['data']['password'])) : 'N/A'));
                $io->writeln("  Roles: " . (!empty($user['data']['roles']) ? implode(', ', $user['data']['roles']) : 'none'));
                $io->writeln("  OpenAI Key: " . (isset($user['data']['openAiApiKey']) ? substr($user['data']['openAiApiKey'], 0, 10) . '...' : 'N/A'));
                $io->newLine();
            }

            // Display errors
            $io->section('ERRORS: ' . count($result['errors']));
            foreach ($result['errors'] as $i => $error) {
                $io->error("Error #" . ($i + 1) . " (Row " . $error['row'] . "):");
                $io->writeln("  Email: " . ($error['data']['email'] ?? 'N/A'));
                $io->writeln("  Name: " . ($error['data']['name'] ?? 'N/A'));
                $io->writeln("  Errors:");
                foreach ($error['errors'] as $errMsg) {
                    $io->writeln("    - " . $errMsg);
                }
                $io->newLine();
            }

            // Try to import if there are valid users
            if (count($result['users']) > 0) {
                $io->section('STEP 2: Attempting to import valid users');

                // Get current user (admin)
                $currentUser = $this->userRepository->findAll()[0] ?? null;

                if (!$currentUser) {
                    $io->error('No user found to act as current user!');
                    return Command::FAILURE;
                }

                $io->info("Importing as user: " . $currentUser->getEmail());
                $io->newLine();

                $importResult = $this->userImportService->importUsers($result['users'], $organization, $currentUser);

                $io->success('Import completed!');
                $io->newLine();

                // Display imported
                $io->section('IMPORTED: ' . count($importResult['imported']));
                foreach ($importResult['imported'] as $i => $imported) {
                    $io->writeln("Imported #" . ($i + 1) . " (Row " . $imported['row'] . "):");
                    $io->writeln("  Email: " . $imported['email']);
                    $io->writeln("  Name: " . $imported['name']);
                    $io->newLine();
                }

                // Display failed
                if (count($importResult['failed']) > 0) {
                    $io->section('FAILED: ' . count($importResult['failed']));
                    foreach ($importResult['failed'] as $i => $failed) {
                        $io->error("Failed #" . ($i + 1) . " (Row " . $failed['row'] . "):");
                        $io->writeln("  Email: " . $failed['email']);
                        $io->writeln("  Error: " . $failed['error']);
                        $io->newLine();
                    }
                }
            }

            $io->success('TEST COMPLETED SUCCESSFULLY');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('ERROR DURING TEST');
            $io->writeln("Exception: " . get_class($e));
            $io->writeln("Message: " . $e->getMessage());
            $io->writeln("File: " . $e->getFile() . ":" . $e->getLine());
            $io->newLine();
            $io->writeln("Stack Trace:");
            $io->writeln($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}
