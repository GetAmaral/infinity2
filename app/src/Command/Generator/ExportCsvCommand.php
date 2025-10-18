<?php

declare(strict_types=1);

namespace App\Command\Generator;

use App\Repository\Generator\GeneratorEntityRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'generator:export-csv',
    description: 'Export GeneratorEntity and GeneratorProperty tables to CSV backup files'
)]
class ExportCsvCommand extends Command
{
    public function __construct(
        private readonly GeneratorEntityRepository $entityRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $timestamp = (new \DateTimeImmutable())->format('Y-m-d_His');

        $backupDir = dirname(__DIR__, 3) . '/config/backup';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $entityFile = sprintf('%s/GeneratorEntity_%s.csv', $backupDir, $timestamp);
        $propertyFile = sprintf('%s/GeneratorProperty_%s.csv', $backupDir, $timestamp);

        $io->title('Generator Database Export');
        $io->text(sprintf('Exporting to: %s', $backupDir));

        // Export GeneratorEntity
        $io->section('Exporting GeneratorEntity');
        $entities = $this->entityRepository->findAllWithProperties();
        $entityCount = $this->exportEntities($entities, $entityFile);
        $io->success(sprintf('Exported %d entities to: %s', $entityCount, basename($entityFile)));

        // Export GeneratorProperty
        $io->section('Exporting GeneratorProperty');
        $propertyCount = $this->exportProperties($entities, $propertyFile);
        $io->success(sprintf('Exported %d properties to: %s', $propertyCount, basename($propertyFile)));

        $io->success('Database export completed successfully!');

        return Command::SUCCESS;
    }

    private function exportEntities(array $entities, string $filePath): int
    {
        $fp = fopen($filePath, 'w');

        // CSV Header
        fputcsv($fp, [
            'id',
            'entity_name',
            'entity_label',
            'plural_label',
            'icon',
            'description',
            'canvas_x',
            'canvas_y',
            'has_organization',
            'api_enabled',
            'api_operations',
            'api_security',
            'api_normalization_context',
            'api_denormalization_context',
            'api_default_order',
            'api_searchable_fields',
            'api_filterable_fields',
            'voter_enabled',
            'voter_attributes',
            'form_theme',
            'custom_index_template',
            'custom_form_template',
            'custom_show_template',
            'menu_group',
            'menu_order',
            'test_enabled',
            'is_generated',
            'last_generated_at',
            'last_generation_log',
            'created_at',
            'updated_at',
        ]);

        $count = 0;
        foreach ($entities as $entity) {
            fputcsv($fp, [
                $entity->getId()->toString(),
                $entity->getEntityName(),
                $entity->getEntityLabel(),
                $entity->getPluralLabel(),
                $entity->getIcon(),
                $entity->getDescription(),
                $entity->getCanvasX(),
                $entity->getCanvasY(),
                $entity->isHasOrganization() ? '1' : '0',
                $entity->isApiEnabled() ? '1' : '0',
                json_encode($entity->getApiOperations()),
                $entity->getApiSecurity(),
                $entity->getApiNormalizationContext(),
                $entity->getApiDenormalizationContext(),
                json_encode($entity->getApiDefaultOrder()),
                json_encode($entity->getApiSearchableFields()),
                json_encode($entity->getApiFilterableFields()),
                $entity->isVoterEnabled() ? '1' : '0',
                json_encode($entity->getVoterAttributes()),
                $entity->getFormTheme(),
                $entity->getCustomIndexTemplate(),
                $entity->getCustomFormTemplate(),
                $entity->getCustomShowTemplate(),
                $entity->getMenuGroup(),
                $entity->getMenuOrder(),
                $entity->isTestEnabled() ? '1' : '0',
                $entity->isGenerated() ? '1' : '0',
                $entity->getLastGeneratedAt()?->format('Y-m-d H:i:s'),
                $entity->getLastGenerationLog(),
                $entity->getCreatedAt()->format('Y-m-d H:i:s'),
                $entity->getUpdatedAt()->format('Y-m-d H:i:s'),
            ]);
            $count++;
        }

        fclose($fp);
        return $count;
    }

    private function exportProperties(array $entities, string $filePath): int
    {
        $fp = fopen($filePath, 'w');

        // CSV Header
        fputcsv($fp, [
            'id',
            'entity_id',
            'entity_name',
            'property_name',
            'property_label',
            'property_type',
            'property_order',
            'nullable',
            'length',
            'precision',
            'scale',
            'is_unique',
            'default_value',
            'relationship_type',
            'target_entity',
            'inversed_by',
            'mapped_by',
            'cascade_actions',
            'orphan_removal',
            'fetch_type',
            'order_by_fields',
            'validation_rules',
            'validation_message',
            'form_type',
            'form_options',
            'form_required',
            'form_read_only',
            'form_help',
            'show_in_list',
            'show_in_detail',
            'show_in_form',
            'sortable',
            'searchable',
            'filterable',
            'api_readable',
            'api_writable',
            'api_groups',
            'translation_key',
            'format_pattern',
            'fixture_type',
            'fixture_options',
            'created_at',
            'updated_at',
        ]);

        $count = 0;
        foreach ($entities as $entity) {
            foreach ($entity->getProperties() as $property) {
                fputcsv($fp, [
                    $property->getId()->toString(),
                    $property->getEntity()->getId()->toString(),
                    $property->getEntity()->getEntityName(),
                    $property->getPropertyName(),
                    $property->getPropertyLabel(),
                    $property->getPropertyType(),
                    $property->getPropertyOrder(),
                    $property->isNullable() ? '1' : '0',
                    $property->getLength(),
                    $property->getPrecision(),
                    $property->getScale(),
                    $property->isUnique() ? '1' : '0',
                    $property->getDefaultValue(),
                    $property->getRelationshipType(),
                    $property->getTargetEntity(),
                    $property->getInversedBy(),
                    $property->getMappedBy(),
                    json_encode($property->getCascade()),
                    $property->isOrphanRemoval() ? '1' : '0',
                    $property->getFetch(),
                    json_encode($property->getOrderBy()),
                    json_encode($property->getValidationRules()),
                    $property->getValidationMessage(),
                    $property->getFormType(),
                    json_encode($property->getFormOptions()),
                    $property->isFormRequired() ? '1' : '0',
                    $property->isFormReadOnly() ? '1' : '0',
                    $property->getFormHelp(),
                    $property->isShowInList() ? '1' : '0',
                    $property->isShowInDetail() ? '1' : '0',
                    $property->isShowInForm() ? '1' : '0',
                    $property->isSortable() ? '1' : '0',
                    $property->isSearchable() ? '1' : '0',
                    $property->isFilterable() ? '1' : '0',
                    $property->isApiReadable() ? '1' : '0',
                    $property->isApiWritable() ? '1' : '0',
                    json_encode($property->getApiGroups()),
                    $property->getTranslationKey(),
                    $property->getFormatPattern(),
                    $property->getFixtureType(),
                    json_encode($property->getFixtureOptions()),
                    $property->getCreatedAt()->format('Y-m-d H:i:s'),
                    $property->getUpdatedAt()->format('Y-m-d H:i:s'),
                ]);
                $count++;
            }
        }

        fclose($fp);
        return $count;
    }
}
