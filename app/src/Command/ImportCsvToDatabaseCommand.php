<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use App\Service\Generator\Csv\CsvParserService;
use App\Service\Generator\Csv\EntityDefinitionDto;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'generator:import-csv',
    description: 'Import entities and properties from CSV files into database'
)]
class ImportCsvToDatabaseCommand extends Command
{
    public function __construct(
        private readonly CsvParserService $csvParser,
        private readonly EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'entity',
                null,
                InputOption::VALUE_REQUIRED,
                'Import only a specific entity (optional)'
            )
            ->addOption(
                'overwrite',
                null,
                InputOption::VALUE_NONE,
                'Overwrite existing entities in database'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $entityFilter = $input->getOption('entity');
        $overwrite = $input->getOption('overwrite');

        $io->title('CSV to Database Import');

        try {
            // Parse CSV files
            $io->section('Parsing CSV files...');
            $result = $this->csvParser->parseAll();
            $rawEntities = $result['entities'];

            if (empty($rawEntities)) {
                $io->error('No entities found in CSV files');
                return Command::FAILURE;
            }

            // Convert to DTOs
            $entities = array_map(
                fn($entity) => EntityDefinitionDto::fromArray($entity),
                $rawEntities
            );

            $io->success(sprintf('Found %d entities in CSV', count($entities)));

            // Filter if specific entity requested
            if ($entityFilter) {
                $entities = array_filter(
                    $entities,
                    fn($entity) => $entity->entityName === $entityFilter
                );

                if (empty($entities)) {
                    $io->error(sprintf('Entity "%s" not found in CSV', $entityFilter));
                    return Command::FAILURE;
                }

                $io->info(sprintf('Importing only: %s', $entityFilter));
            }

            // Check for existing entities
            $existingCount = $this->em->getRepository(GeneratorEntity::class)->count([]);
            if ($existingCount > 0 && !$overwrite) {
                $io->warning(sprintf(
                    'Database already contains %d entities. Use --overwrite to replace them.',
                    $existingCount
                ));

                if (!$io->confirm('Continue importing (will skip duplicates)?', false)) {
                    $io->info('Import cancelled');
                    return Command::SUCCESS;
                }
            }

            // Import entities
            $io->section('Importing entities to database...');
            $progressBar = $io->createProgressBar(count($entities));
            $progressBar->start();

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($entities as $entityDto) {
                try {
                    // Check if entity exists
                    $existingEntity = $this->em->getRepository(GeneratorEntity::class)
                        ->findOneBy(['entityName' => $entityDto->entityName]);

                    if ($existingEntity && !$overwrite) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    // Create or update entity
                    $generatorEntity = $existingEntity ?? new GeneratorEntity();

                    // Map DTO to entity
                    $generatorEntity->setEntityName($entityDto->entityName);
                    $generatorEntity->setEntityLabel($entityDto->entityLabel);
                    $generatorEntity->setPluralLabel($entityDto->pluralLabel);
                    $generatorEntity->setIcon($entityDto->icon);
                    $generatorEntity->setDescription($entityDto->description);
                    $generatorEntity->setHasOrganization($entityDto->hasOrganization);

                    // API
                    $generatorEntity->setApiEnabled($entityDto->apiEnabled);
                    $generatorEntity->setApiOperations($entityDto->operations);
                    $generatorEntity->setApiSecurity($entityDto->security);
                    $generatorEntity->setApiNormalizationContext($entityDto->normalizationContext);
                    $generatorEntity->setApiDenormalizationContext($entityDto->denormalizationContext);
                    $generatorEntity->setApiPaginationEnabled($entityDto->paginationEnabled);
                    $generatorEntity->setApiItemsPerPage($entityDto->itemsPerPage);
                    $generatorEntity->setApiDefaultOrder($entityDto->order);
                    $generatorEntity->setApiSearchableFields($entityDto->searchableFields);
                    $generatorEntity->setApiFilterableFields($entityDto->filterableFields);

                    // Security
                    $generatorEntity->setVoterEnabled($entityDto->voterEnabled);
                    $generatorEntity->setVoterAttributes($entityDto->voterAttributes);

                    // Form
                    $generatorEntity->setFormTheme($entityDto->formTheme);

                    // Templates
                    $generatorEntity->setCustomIndexTemplate($entityDto->indexTemplate ?: null);
                    $generatorEntity->setCustomFormTemplate($entityDto->formTemplate ?: null);
                    $generatorEntity->setCustomShowTemplate($entityDto->showTemplate ?: null);

                    // Navigation
                    $generatorEntity->setMenuGroup($entityDto->menuGroup ?: null);
                    $generatorEntity->setMenuOrder($entityDto->menuOrder);

                    // Testing
                    $generatorEntity->setTestEnabled($entityDto->testEnabled);

                    if (!$existingEntity) {
                        $this->em->persist($generatorEntity);
                    }

                    // Import properties
                    if ($overwrite && $existingEntity) {
                        // Remove old properties
                        foreach ($existingEntity->getProperties() as $oldProp) {
                            $this->em->remove($oldProp);
                        }
                        $this->em->flush();
                    }

                    foreach ($entityDto->properties as $propertyDto) {
                        $generatorProperty = new GeneratorProperty();
                        $generatorProperty->setEntity($generatorEntity);

                        // Basic
                        $generatorProperty->setPropertyName($propertyDto->propertyName);
                        $generatorProperty->setPropertyLabel($propertyDto->propertyLabel);
                        $generatorProperty->setPropertyType($propertyDto->propertyType);
                        $generatorProperty->setPropertyOrder(0);

                        // Database
                        $generatorProperty->setNullable($propertyDto->nullable);
                        $generatorProperty->setLength($propertyDto->length);
                        $generatorProperty->setPrecision($propertyDto->precision);
                        $generatorProperty->setScale($propertyDto->scale);
                        $generatorProperty->setUnique($propertyDto->unique);
                        $generatorProperty->setDefaultValue($propertyDto->defaultValue);

                        // Relationships
                        $generatorProperty->setRelationshipType($propertyDto->relationshipType);
                        $generatorProperty->setTargetEntity($propertyDto->targetEntity);
                        $generatorProperty->setInversedBy($propertyDto->inversedBy);
                        $generatorProperty->setMappedBy($propertyDto->mappedBy);
                        $generatorProperty->setCascade($propertyDto->cascade);
                        $generatorProperty->setOrphanRemoval($propertyDto->orphanRemoval);
                        $generatorProperty->setFetch($propertyDto->fetch);
                        $generatorProperty->setOrderBy($propertyDto->orderBy);

                        // Validation
                        $generatorProperty->setValidationRules($propertyDto->validationRules);
                        $generatorProperty->setValidationMessage($propertyDto->validationMessage);

                        // Form
                        $generatorProperty->setFormType($propertyDto->formType);
                        $generatorProperty->setFormOptions($propertyDto->formOptions);
                        $generatorProperty->setFormRequired($propertyDto->formRequired);
                        $generatorProperty->setFormReadOnly($propertyDto->formReadOnly);
                        $generatorProperty->setFormHelp($propertyDto->formHelp);

                        // UI
                        $generatorProperty->setShowInList($propertyDto->showInList);
                        $generatorProperty->setShowInDetail($propertyDto->showInDetail);
                        $generatorProperty->setShowInForm($propertyDto->showInForm);
                        $generatorProperty->setSortable($propertyDto->sortable);
                        $generatorProperty->setSearchable($propertyDto->searchable);
                        $generatorProperty->setFilterable($propertyDto->filterable);

                        // API
                        $generatorProperty->setApiReadable($propertyDto->apiReadable);
                        $generatorProperty->setApiWritable($propertyDto->apiWritable);
                        $generatorProperty->setApiGroups($propertyDto->apiGroups);

                        // Localization
                        $generatorProperty->setTranslationKey($propertyDto->translationKey);
                        $generatorProperty->setFormatPattern($propertyDto->formatPattern);

                        // Fixtures
                        $generatorProperty->setFixtureType($propertyDto->fixtureType);
                        $generatorProperty->setFixtureOptions($propertyDto->fixtureOptions);

                        $this->em->persist($generatorProperty);
                    }

                    $this->em->flush();
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = sprintf(
                        '%s: %s',
                        $entityDto->entityName,
                        $e->getMessage()
                    );
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $io->newLine(2);

            // Display results
            $io->section('Import Results');
            $io->table(
                ['Metric', 'Value'],
                [
                    ['Total Entities in CSV', count($entities)],
                    ['Successfully Imported', $imported],
                    ['Skipped (duplicates)', $skipped],
                    ['Errors', count($errors)],
                ]
            );

            if (!empty($errors)) {
                $io->section('Errors');
                foreach ($errors as $error) {
                    $io->writeln('  • ' . $error);
                }
                return Command::FAILURE;
            }

            $io->success('CSV import completed successfully');

            // Show next steps
            $io->section('Next Steps');
            $io->listing([
                'Visit Generator Studio: /admin/generator/studio',
                'Review imported entities and properties',
                'Adjust canvas positions and relationships',
                'Generate code from database entities',
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error([
                'Import failed with exception:',
                $e->getMessage(),
                sprintf('File: %s:%d', $e->getFile(), $e->getLine())
            ]);
            return Command::FAILURE;
        }
    }
}
