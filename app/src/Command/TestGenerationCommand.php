<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Generator\GeneratorEntity;
use App\Service\Generator\DatabaseDefinitionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'generator:test-generation',
    description: 'Test code generation from a database entity'
)]
class TestGenerationCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DatabaseDefinitionService $dbService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('entityName', InputArgument::OPTIONAL, 'Entity name to generate', 'Agent');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $entityName = $input->getArgument('entityName');

        $io->title('Test Code Generation from Database');

        // Find entity
        $entity = $this->em->getRepository(GeneratorEntity::class)
            ->findOneBy(['entityName' => $entityName]);

        if (!$entity) {
            $io->error(sprintf('Entity "%s" not found in database', $entityName));
            return Command::FAILURE;
        }

        $io->success(sprintf('Found entity "%s" with %d properties',
            $entity->getEntityName(),
            $entity->getProperties()->count()
        ));

        // Build definition
        $io->section('Building Entity Definition');
        $definition = $this->dbService->buildEntityDefinition($entity);

        $io->table(
            ['Field', 'Value'],
            [
                ['Entity Name', $definition['entityName']],
                ['Label', $definition['entityLabel']],
                ['Plural', $definition['pluralLabel']],
                ['Icon', $definition['icon']],
                ['Properties', count($definition['properties'])],
                ['API Enabled', $definition['apiEnabled'] ? 'Yes' : 'No'],
                ['Voter Enabled', $definition['voterEnabled'] ? 'Yes' : 'No'],
            ]
        );

        // Preview generation
        $io->section('Preview Generation');
        $preview = $this->dbService->previewGeneration($definition);
        foreach ($preview as $group => $files) {
            if (is_array($files)) {
                $io->writeln("  <info>$group:</info>");
                foreach ($files as $file) {
                    $io->writeln("    - $file");
                }
            } else {
                $io->writeln("  <info>$group:</info> $files");
            }
        }

        // Generate files
        $io->section('Generating Files');
        $io->write('Generating... ');

        try {
            $files = $this->dbService->generateAllFiles($definition);
            $io->writeln('<info>Done!</info>');

            $totalFiles = 0;
            foreach ($files as $group => $groupFiles) {
                $count = is_array($groupFiles) ? count($groupFiles) : 1;
                $totalFiles += $count;
                $io->writeln("  <comment>$group:</comment> $count files");
            }

            $io->success(sprintf('Successfully generated %d files for %s', $totalFiles, $entityName));

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Generation failed:',
                $e->getMessage(),
                '',
                'File: ' . $e->getFile() . ':' . $e->getLine()
            ]);
            return Command::FAILURE;
        }
    }
}
