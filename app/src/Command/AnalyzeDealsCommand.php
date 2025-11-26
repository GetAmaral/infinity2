<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\DealRepository;
use App\Service\AI\DealScoringService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analyze-deals',
    description: 'Batch analyze deals using AI'
)]
class AnalyzeDealsCommand extends Command
{
    public function __construct(
        private DealRepository $dealRepo,
        private DealScoringService $scoringService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Maximum deals to analyze', 100);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = (int)$input->getOption('limit');

        $deals = $this->dealRepo->findNeedingAiAnalysis($limit);

        $io->title(sprintf('Analyzing %d deals...', count($deals)));

        $success = 0;
        $failed = 0;

        foreach ($deals as $deal) {
            try {
                $this->scoringService->analyzeDeal($deal);
                $io->success(sprintf('✓ %s (Score: %d)', $deal->getName(), $deal->getAiScore()));
                $success++;
            } catch (\Exception $e) {
                $io->error(sprintf('✗ %s: %s', $deal->getName(), $e->getMessage()));
                $failed++;
            }
        }

        $io->success(sprintf('Analysis complete: %d successful, %d failed', $success, $failed));

        return Command::SUCCESS;
    }
}
