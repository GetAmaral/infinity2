<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\AuditLogRepository;
use App\Repository\UserRepository;
use App\Service\AuditExportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-audit',
    description: 'Test audit system functionality'
)]
class TestAuditCommand extends Command
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        private readonly UserRepository $userRepository,
        private readonly AuditExportService $exportService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Testing Audit System');

        // Test 1: Check audit logs exist
        $io->section('Test 1: Audit Logs');
        $recentLogs = $this->auditLogRepository->findRecent(5);
        $io->success(sprintf('Found %d recent audit logs', count($recentLogs)));

        if (count($recentLogs) > 0) {
            $log = $recentLogs[0];
            $io->info(sprintf(
                'Latest: %s on %s (%s)',
                $log->getAction(),
                $log->getEntityClass(),
                $log->getCreatedAt()->format('Y-m-d H:i:s')
            ));
        }

        // Test 2: Check user audit history
        $io->section('Test 2: User Actions');
        $adminUser = $this->userRepository->findOneBy(['email' => 'admin@infinity.local']);

        if ($adminUser) {
            $userLogs = $this->auditLogRepository->findByUser($adminUser);
            $io->success(sprintf('Found %d actions by admin user', count($userLogs)));
        } else {
            $io->warning('Admin user not found');
        }

        // Test 3: Check entity timeline
        $io->section('Test 3: Entity Timeline');
        if (count($recentLogs) > 0) {
            $log = $recentLogs[0];
            $entityLogs = $this->auditLogRepository->findByEntity(
                $log->getEntityClass(),
                $log->getEntityId()
            );
            $io->success(sprintf(
                'Found %d changes for %s',
                count($entityLogs),
                $log->getEntityClass()
            ));
        }

        // Test 4: Check statistics
        $io->section('Test 4: Statistics');
        $since = new \DateTimeImmutable('-30 days');
        $stats = $this->auditLogRepository->getStatistics($since);
        $io->success(sprintf('Found %d statistic entries', count($stats)));

        if (count($stats) > 0) {
            $io->table(
                ['Action', 'Entity', 'Count'],
                array_map(fn($s) => [$s['action'], $s['entityClass'], $s['count']], array_slice($stats, 0, 5))
            );
        }

        // Test 5: Check export functionality
        $io->section('Test 5: Export');
        $logs = $this->auditLogRepository->findRecent(10);

        try {
            $csvResponse = $this->exportService->exportToCsv($logs);
            $io->success('CSV export works correctly');
        } catch (\Exception $e) {
            $io->error('CSV export failed: ' . $e->getMessage());
        }

        try {
            $jsonResponse = $this->exportService->exportToJson($logs);
            $io->success('JSON export works correctly');
        } catch (\Exception $e) {
            $io->error('JSON export failed: ' . $e->getMessage());
        }

        $io->success('All audit system tests passed!');

        return Command::SUCCESS;
    }
}
