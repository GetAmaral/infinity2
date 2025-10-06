<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\TreeFlow;
use App\Entity\Step;
use App\Entity\StepQuestion;
use App\Entity\StepInput;
use App\Entity\StepOutput;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-slugs',
    description: 'Fix all slugs in all entities by regenerating them from names',
)]
class FixSlugsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Fixing slugs for all entities');

        $totalFixed = 0;

        // Fix TreeFlow slugs
        $io->section('Fixing TreeFlow slugs');
        $treeFlows = $this->entityManager->getRepository(TreeFlow::class)->findAll();
        foreach ($treeFlows as $treeFlow) {
            $oldSlug = $treeFlow->getSlug();
            $newSlug = Utils::stringToSlug($treeFlow->getName());
            if ($oldSlug !== $newSlug) {
                $treeFlow->setSlug($newSlug);
                $io->writeln("  TreeFlow: '{$treeFlow->getName()}' | {$oldSlug} → {$newSlug}");
                $totalFixed++;
            }
        }

        // Fix Step slugs
        $io->section('Fixing Step slugs');
        $steps = $this->entityManager->getRepository(Step::class)->findAll();
        foreach ($steps as $step) {
            $oldSlug = $step->getSlug();
            $newSlug = Utils::stringToSlug($step->getName());
            if ($oldSlug !== $newSlug) {
                $step->setSlug($newSlug);
                $io->writeln("  Step: '{$step->getName()}' | {$oldSlug} → {$newSlug}");
                $totalFixed++;
            }
        }

        // Fix StepQuestion slugs
        $io->section('Fixing StepQuestion slugs');
        $questions = $this->entityManager->getRepository(StepQuestion::class)->findAll();
        foreach ($questions as $question) {
            $oldSlug = $question->getSlug();
            $newSlug = Utils::stringToSlug($question->getName());
            if ($oldSlug !== $newSlug) {
                $question->setSlug($newSlug);
                $io->writeln("  Question: '{$question->getName()}' | {$oldSlug} → {$newSlug}");
                $totalFixed++;
            }
        }

        // Fix StepInput slugs (only if slug is not null)
        $io->section('Fixing StepInput slugs');
        $inputs = $this->entityManager->getRepository(StepInput::class)->findAll();
        foreach ($inputs as $input) {
            $oldSlug = $input->getSlug();
            $newSlug = Utils::stringToSlug($input->getName());
            if ($oldSlug !== null && $oldSlug !== $newSlug) {
                $input->setSlug($newSlug);
                $io->writeln("  Input: '{$input->getName()}' | {$oldSlug} → {$newSlug}");
                $totalFixed++;
            }
        }

        // Fix StepOutput slugs (only if slug is not null)
        $io->section('Fixing StepOutput slugs');
        $outputs = $this->entityManager->getRepository(StepOutput::class)->findAll();
        foreach ($outputs as $output) {
            $oldSlug = $output->getSlug();
            $newSlug = Utils::stringToSlug($output->getName());
            if ($oldSlug !== null && $oldSlug !== $newSlug) {
                $output->setSlug($newSlug);
                $io->writeln("  Output: '{$output->getName()}' | {$oldSlug} → {$newSlug}");
                $totalFixed++;
            }
        }

        // Flush all changes
        $this->entityManager->flush();

        $io->success("Fixed {$totalFixed} slugs across all entities!");

        return Command::SUCCESS;
    }
}
