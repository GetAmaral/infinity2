<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\TreeFlow;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:regenerate-treeflow-json',
    description: 'Regenerate cached JSON structure for all TreeFlows',
)]
class RegenerateTreeFlowJsonCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'force',
            'f',
            InputOption::VALUE_NONE,
            'Force regeneration even if JSON already exists'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $io->title('Regenerating TreeFlow JSON Cache (jsonStructure + talkFlow)');

        $treeFlows = $this->entityManager->getRepository(TreeFlow::class)->findAll();
        $total = count($treeFlows);

        if ($total === 0) {
            $io->warning('No TreeFlows found in the database.');
            return Command::SUCCESS;
        }

        $io->writeln("Found {$total} TreeFlow(s)\n");

        $regenerated = 0;
        $skipped = 0;

        foreach ($treeFlows as $treeFlow) {
            $hasBothCaches = $treeFlow->getJsonStructure() !== null && $treeFlow->getTalkFlow() !== null;

            if ($hasBothCaches && !$force) {
                $io->writeln("⏭️  Skipped: {$treeFlow->getName()} (already has both caches)");
                $skipped++;
                continue;
            }

            $io->write("🔄 Regenerating: {$treeFlow->getName()}... ");

            try {
                // Generate both structures
                $jsonStructure = $treeFlow->convertToJson();
                $talkFlow = $treeFlow->convertToTalkFlow();

                // Set both caches
                $treeFlow->setJsonStructure($jsonStructure);
                $treeFlow->setTalkFlow($talkFlow);

                $this->entityManager->flush();

                $stepCount = count($jsonStructure[$treeFlow->getSlug()]['steps'] ?? []);
                $io->writeln("✅ Done ({$stepCount} steps, both caches generated)");
                $regenerated++;
            } catch (\Exception $e) {
                $io->writeln("❌ Failed: " . $e->getMessage());
            }
        }

        $io->newLine();
        $io->success([
            "Regenerated: {$regenerated} TreeFlows",
            "Skipped: {$skipped}",
            "Total: {$total}",
            "",
            "Each TreeFlow now has both:",
            "  • jsonStructure (complete data)",
            "  • talkFlow (empty template)"
        ]);

        if ($skipped > 0 && !$force) {
            $io->note('Use --force to regenerate TreeFlows that already have cached data');
        }

        return Command::SUCCESS;
    }
}
