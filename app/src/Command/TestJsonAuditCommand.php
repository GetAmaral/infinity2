<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Test command to demonstrate JSON field auditing with granular change tracking
 */
#[AsCommand(
    name: 'app:test:json-audit',
    description: 'Test JSON field auditing - modifies user uiSettings to demonstrate granular change tracking'
)]
class TestJsonAuditCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing JSON Field Audit - Granular Change Tracking');

        // Find the first user with existing UI settings
        $user = $this->userRepository->createQueryBuilder('u')
            ->where('u.uiSettings IS NOT NULL')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        // If no user with settings, find any user
        if (!$user) {
            $user = $this->userRepository->findOneBy([]);
        }

        if (!$user) {
            $io->error('No users found in database');
            return Command::FAILURE;
        }

        $io->info(sprintf('Testing with user: %s', $user->getEmail()));

        // Display current uiSettings
        $currentSettings = $user->getUiSettings();
        $io->section('Current UI Settings');
        $io->writeln(json_encode($currentSettings, JSON_PRETTY_PRINT) ?: 'null');

        // Create initial settings if none exist
        if ($currentSettings === null) {
            $io->info('No settings found - creating initial settings');
            $user->setUiSettings([
                'theme' => 'light',
                'language' => 'en',
                'notifications' => [
                    'email' => true,
                    'push' => false,
                    'sms' => false
                ],
                'preferences' => [
                    'compactView' => false,
                    'autoSave' => true
                ]
            ]);

            $this->entityManager->flush();
            $io->success('Initial settings created');
            return Command::SUCCESS;
        }

        // Modify only specific keys in the JSON
        $io->section('Modifying JSON Settings');

        $newSettings = $currentSettings;

        // Change theme
        $newSettings['theme'] = $currentSettings['theme'] === 'light' ? 'dark' : 'light';
        $io->writeln(sprintf('Changed theme: %s → %s', $currentSettings['theme'], $newSettings['theme']));

        // Modify nested notification setting
        if (isset($newSettings['notifications']['email'])) {
            $newSettings['notifications']['email'] = !$currentSettings['notifications']['email'];
            $io->writeln(sprintf(
                'Changed notifications.email: %s → %s',
                $currentSettings['notifications']['email'] ? 'true' : 'false',
                $newSettings['notifications']['email'] ? 'true' : 'false'
            ));
        }

        // Add a new key
        $newSettings['lastLogin'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $io->writeln(sprintf('Added lastLogin: %s', $newSettings['lastLogin']));

        // Apply changes
        $user->setUiSettings($newSettings);
        $this->entityManager->flush();

        $io->section('New UI Settings');
        $io->writeln(json_encode($newSettings, JSON_PRETTY_PRINT));

        $io->success([
            'JSON field updated successfully!',
            'The audit log will show ONLY the changed keys:',
            '- theme: old → new value',
            '- notifications.email: old → new value',
            '- lastLogin: null → new value (added)',
            '',
            'Check the audit log to see granular tracking:'
        ]);

        $io->info('Run: docker-compose exec app php bin/console doctrine:query:sql "SELECT action, changes FROM audit_log WHERE entity_class LIKE \'%User%\' ORDER BY created_at DESC LIMIT 1"');

        return Command::SUCCESS;
    }
}
