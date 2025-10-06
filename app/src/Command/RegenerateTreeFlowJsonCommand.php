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

        $io->title('Regenerating TreeFlow JSON Cache');

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
            $hasCache = $treeFlow->getJsonStructure() !== null;

            if ($hasCache && !$force) {
                $io->writeln("⏭️  Skipped: {$treeFlow->getName()} (already has cached JSON)");
                $skipped++;
                continue;
            }

            $io->write("🔄 Regenerating: {$treeFlow->getName()}... ");

            try {
                $jsonStructure = $treeFlow->convertToJson();
                $treeFlow->setJsonStructure($jsonStructure);
                $this->entityManager->flush();

                $stepCount = count($jsonStructure[$treeFlow->getSlug()]['steps'] ?? []);
                $io->writeln("✅ Done ({$stepCount} steps)");
                $regenerated++;
            } catch (\Exception $e) {
                $io->writeln("❌ Failed: " . $e->getMessage());
            }
        }

        $io->newLine();
        $io->success([
            "Regenerated: {$regenerated}",
            "Skipped: {$skipped}",
            "Total: {$total}"
        ]);

        if ($skipped > 0 && !$force) {
            $io->note('Use --force to regenerate TreeFlows that already have cached JSON');
        }

        return Command::SUCCESS;
    }
}
