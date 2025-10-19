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
            'menu_group',
            'menu_order',
            'test_enabled',
            'namespace',
            'table_name',
            'fixtures_enabled',
            'audit_enabled',
            'color',
            'tags',
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
                json_encode($entity->getApiNormalizationContext()),
                json_encode($entity->getApiDenormalizationContext()),
                json_encode($entity->getApiDefaultOrder()),
                json_encode($entity->getApiSearchableFields()),
                json_encode($entity->getApiFilterableFields()),
                $entity->isVoterEnabled() ? '1' : '0',
                json_encode($entity->getVoterAttributes()),
                $entity->getMenuGroup(),
                $entity->getMenuOrder(),
                $entity->isTestEnabled() ? '1' : '0',
                $entity->getNamespace(),
                $entity->getTableNameValue(),
                $entity->isFixturesEnabled() ? '1' : '0',
                $entity->isAuditEnabled() ? '1' : '0',
                $entity->getColor(),
                json_encode($entity->getTags()),
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
            // Database Configuration
            'nullable',
            'length',
            'precision',
            'scale',
            'unique',
            'default_value',
            'indexed',
            'index_type',
            'composite_index_with',
            // Enum Support
            'is_enum',
            'enum_class',
            'enum_values',
            // Computed/Virtual Properties
            'is_virtual',
            'compute_expression',
            'use_property_hook',
            // PostgreSQL-Specific Features
            'is_jsonb',
            'use_full_text_search',
            'is_array_type',
            'pg_array_type',
            'check_constraint',
            // Relationships
            'relationship_type',
            'target_entity',
            'inversed_by',
            'mapped_by',
            'cascade',
            'orphan_removal',
            'fetch',
            'order_by',
            // Embedded Objects
            'is_embedded',
            'embedded_class',
            'embedded_prefix',
            // Validation
            'validation_rules',
            'validation_message',
            'validation_groups',
            'custom_validator',
            'validation_condition',
            // Form Configuration
            'form_type',
            'form_options',
            'form_required',
            'form_read_only',
            'form_help',
            // UI Display
            'show_in_list',
            'show_in_detail',
            'show_in_form',
            'sortable',
            'searchable',
            'filterable',
            // API Configuration
            'api_readable',
            'api_writable',
            'api_groups',
            'is_subresource',
            'subresource_path',
            'expose_iri',
            'api_description',
            'api_example',
            // Field-Level Security
            'allowed_roles',
            // Localization
            'translation_key',
            'format_pattern',
            // Serialization Control
            'serializer_context',
            'serializer_method',
            'denormalizer',
            // Fixtures
            'fixture_type',
            'fixture_options',
            // Audit
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
                    // Database Configuration
                    $property->isNullable() ? '1' : '0',
                    $property->getLength(),
                    $property->getPrecision(),
                    $property->getScale(),
                    $property->isUnique() ? '1' : '0',
                    json_encode($property->getDefaultValue()),
                    $property->isIndexed() ? '1' : '0',
                    $property->getIndexType(),
                    json_encode($property->getCompositeIndexWith()),
                    // Enum Support
                    $property->isEnum() ? '1' : '0',
                    $property->getEnumClass(),
                    json_encode($property->getEnumValues()),
                    // Computed/Virtual Properties
                    $property->isVirtual() ? '1' : '0',
                    $property->getComputeExpression(),
                    $property->isUsePropertyHook() ? '1' : '0',
                    // PostgreSQL-Specific Features
                    $property->isJsonb() ? '1' : '0',
                    $property->isUseFullTextSearch() ? '1' : '0',
                    $property->isArrayType() ? '1' : '0',
                    $property->getPgArrayType(),
                    $property->getCheckConstraint(),
                    // Relationships
                    $property->getRelationshipType(),
                    $property->getTargetEntity(),
                    $property->getInversedBy(),
                    $property->getMappedBy(),
                    json_encode($property->getCascade()),
                    $property->isOrphanRemoval() ? '1' : '0',
                    $property->getFetch(),
                    json_encode($property->getOrderBy()),
                    // Embedded Objects
                    $property->isEmbedded() ? '1' : '0',
                    $property->getEmbeddedClass(),
                    $property->getEmbeddedPrefix(),
                    // Validation
                    json_encode($property->getValidationRules()),
                    $property->getValidationMessage(),
                    json_encode($property->getValidationGroups()),
                    $property->getCustomValidator(),
                    $property->getValidationCondition(),
                    // Form Configuration
                    $property->getFormType(),
                    json_encode($property->getFormOptions()),
                    $property->isFormRequired() ? '1' : '0',
                    $property->isFormReadOnly() ? '1' : '0',
                    $property->getFormHelp(),
                    // UI Display
                    $property->isShowInList() ? '1' : '0',
                    $property->isShowInDetail() ? '1' : '0',
                    $property->isShowInForm() ? '1' : '0',
                    $property->isSortable() ? '1' : '0',
                    $property->isSearchable() ? '1' : '0',
                    $property->isFilterable() ? '1' : '0',
                    // API Configuration
                    $property->isApiReadable() ? '1' : '0',
                    $property->isApiWritable() ? '1' : '0',
                    json_encode($property->getApiGroups()),
                    $property->isSubresource() ? '1' : '0',
                    $property->getSubresourcePath(),
                    $property->isExposeIri() ? '1' : '0',
                    $property->getApiDescription(),
                    $property->getApiExample(),
                    // Field-Level Security
                    json_encode($property->getAllowedRoles()),
                    // Localization
                    $property->getTranslationKey(),
                    $property->getFormatPattern(),
                    // Serialization Control
                    json_encode($property->getSerializerContext()),
                    $property->getSerializerMethod(),
                    $property->getDenormalizer(),
                    // Fixtures
                    $property->getFixtureType(),
                    json_encode($property->getFixtureOptions()),
                    // Audit
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
