<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\AuditAlertService;
use App\Service\AuditAnalyticsService;
use App\Service\PredictiveAnalyticsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to generate weekly analytics reports
 *
 * Generates comprehensive analytics report with metrics, anomalies,
 * and predictions. Can be scheduled via cron for automated reporting.
 */
#[AsCommand(
    name: 'app:audit:analytics:report',
    description: 'Generate weekly audit analytics report'
)]
class AuditAnalyticsReportCommand extends Command
{
    public function __construct(
        private readonly AuditAnalyticsService $analyticsService,
        private readonly PredictiveAnalyticsService $predictiveService,
        private readonly AuditAlertService $alertService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'send-alerts',
                null,
                InputOption::VALUE_NONE,
                'Send alerts for detected anomalies'
            )
            ->setHelp(<<<'HELP'
This command generates a comprehensive analytics report including:
- Summary statistics (total events, weekly trends)
- Top active users
- Most modified entities
- Detected anomalies
- Predictions for next week
- Capacity recommendations

The report is displayed in the console and can be logged for auditing.
Use --send-alerts to trigger alert notifications for anomalies.

Examples:
  # Generate report
  php bin/console app:audit:analytics:report

  # Generate report and send alerts
  php bin/console app:audit:analytics:report --send-alerts

Cron Job Setup:
  # Weekly report every Monday at 9:00 AM
  0 9 * * 1 cd /app && php bin/console app:audit:analytics:report --send-alerts --env=prod
HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sendAlerts = $input->getOption('send-alerts');

        $io->title('Weekly Audit Analytics Report');
        $io->info(sprintf('Generated: %s', (new \DateTimeImmutable())->format('Y-m-d H:i:s')));

        // Section 1: Summary Statistics
        $io->section('Summary Statistics');

        $summary = $this->analyticsService->getSummaryStatistics();

        $io->table(
            ['Metric', 'Value'],
            [
                ['Total Events', number_format($summary['total_events'])],
                ['Events Today', number_format($summary['events_today'])],
                ['Events This Week', number_format($summary['events_week'])],
                ['Anomalies Detected', $summary['anomaly_count']],
            ]
        );

        // Section 2: Top Active Users
        $io->section('Top Active Users (Last 7 Days)');

        $topUsers = $this->analyticsService->getTopActiveUsers(10);

        if (empty($topUsers)) {
            $io->info('No user activity data available');
        } else {
            $userRows = [];
            foreach ($topUsers as $row) {
                $userRows[] = [
                    $row[0]->getEmail(),
                    $row['action_count'],
                ];
            }

            $io->table(['User', 'Actions'], $userRows);
        }

        // Section 3: Most Modified Entities
        $io->section('Most Modified Entities (Last 7 Days)');

        $topEntities = $this->analyticsService->getMostModifiedEntities(10);

        if (empty($topEntities)) {
            $io->info('No entity modification data available');
        } else {
            $entityRows = [];
            foreach ($topEntities as $row) {
                $shortName = substr($row['entity_class'], strrpos($row['entity_class'], '\\') + 1);
                $entityRows[] = [
                    $shortName,
                    $row['modification_count'],
                ];
            }

            $io->table(['Entity Type', 'Modifications'], $entityRows);
        }

        // Section 4: Anomalies
        $io->section('Detected Anomalies');

        $anomalies = $this->analyticsService->detectAnomalies();

        if (empty($anomalies)) {
            $io->success('No anomalies detected');
        } else {
            $io->warning(sprintf('%d anomal%s detected', count($anomalies), count($anomalies) > 1 ? 'ies' : 'y'));

            foreach ($anomalies as $anomaly) {
                $io->writeln(sprintf(
                    '  [%s] %s: %s',
                    strtoupper($anomaly['severity']),
                    str_replace('_', ' ', ucwords($anomaly['type'], '_')),
                    $anomaly['message']
                ));
            }

            // Send alerts if requested
            if ($sendAlerts) {
                $this->alertService->sendAnomalyAlerts($anomalies);
                $io->info('Anomaly alerts sent to security channel');
            }
        }

        // Section 5: Predictions
        $io->section('Predictions');

        $prediction = $this->predictiveService->predictNextWeekActivity();

        $io->table(
            ['Metric', 'Value'],
            [
                ['Predicted Events Next Week', number_format($prediction['predicted_events'])],
                ['Trend', strtoupper($prediction['trend'])],
                ['Confidence', sprintf('%.1f%%', $prediction['confidence'] * 100)],
            ]
        );

        // Section 6: Capacity Recommendation
        $io->section('Capacity Recommendation');

        $capacity = $this->predictiveService->getCapacityRecommendation();

        switch ($capacity['status']) {
            case 'warning':
                $io->warning($capacity['message']);
                break;
            case 'info':
                $io->info($capacity['message']);
                break;
            default:
                $io->success($capacity['message']);
        }

        $io->writeln(sprintf('Recommendation: %s', $capacity['recommendation']));

        // Send capacity alert if warning
        if ($sendAlerts && $capacity['status'] === 'warning') {
            $this->alertService->sendCapacityAlert($capacity);
            $io->info('Capacity alert sent to security channel');
        }

        // Summary
        $io->newLine();
        $io->success('Weekly analytics report completed');

        return Command::SUCCESS;
    }
}
