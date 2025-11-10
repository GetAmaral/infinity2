<?php

declare(strict_types=1);

namespace App\Service\Genmax;

use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Form Generator for Genmax
 *
 * Generates Symfony forms using Base/Extension pattern.
 * All forms generated from GeneratorEntity database configuration.
 *
 * @see /app/docs/Genmax/FORM_GENERATOR.md
 */
class FormGenerator
{
    public function __construct(
        #[Autowire(param: 'kernel.project_dir')]
        protected readonly string $projectDir,
        #[Autowire(param: 'genmax.paths')]
        protected readonly array $paths,
        #[Autowire(param: 'genmax.templates')]
        protected readonly array $templates,
        protected readonly Environment $twig,
        protected readonly SmartFileWriter $fileWriter,
        protected readonly GenmaxExtension $genmaxExtension,
        protected readonly TranslatorInterface $translator,
        protected readonly LoggerInterface $logger
    ) {}

    /**
     * Generate form files for a GeneratorEntity
     *
     * @param GeneratorEntity $entity
     * @return array<string> Array of generated file paths
     */
    public function generate(GeneratorEntity $entity): array
    {
        $this->logger->info('[GENMAX] Generating form', [
            'entity' => $entity->getEntityName()
        ]);

        $generatedFiles = [];

        // Generate base form (always regenerated)
        $generatedFiles[] = $this->generateBaseForm($entity);

        // Generate extension form (once only)
        $extensionFile = $this->generateExtensionForm($entity);
        if ($extensionFile) {
            $generatedFiles[] = $extensionFile;
        }

        // Generate translation keys
        $this->generateTranslationKeys($entity);

        return array_filter($generatedFiles);
    }

    /**
     * Generate base form class: src/Form/Generated/{Entity}TypeGenerated.php
     */
    protected function generateBaseForm(GeneratorEntity $entity): string
    {
        $filePath = sprintf(
            '%s/%s/%sTypeGenerated.php',
            $this->projectDir,
            $this->paths['form_generated_dir'],
            $entity->getEntityName()
        );

        try {
            $context = $this->buildTemplateContext($entity);

            // Render from template
            $content = $this->twig->render($this->templates['form_generated'], $context);

            // Write file with smart comparison
            $status = $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated form base class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate form base class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate form base class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Generate extension form class: src/Form/{Entity}Type.php
     */
    protected function generateExtensionForm(GeneratorEntity $entity): ?string
    {
        $filePath = sprintf(
            '%s/%s/%sType.php',
            $this->projectDir,
            $this->paths['form_dir'],
            $entity->getEntityName()
        );

        // Skip if exists (user may have customized)
        if (file_exists($filePath)) {
            $this->logger->info('[GENMAX] Skipping extension form (already exists)', [
                'file' => $filePath,
                'entity' => $entity->getEntityName()
            ]);
            return null;
        }

        try {
            $context = $this->buildTemplateContext($entity);

            // Render from template
            $content = $this->twig->render($this->templates['form_extension'], $context);

            // Write file with smart comparison
            $status = $this->fileWriter->writeFile($filePath, $content);

            $this->logger->info('[GENMAX] Generated form extension class', [
                'file' => $filePath,
                'entity' => $entity->getEntityName(),
                'status' => $status
            ]);

            return $filePath;

        } catch (\Exception $e) {
            $this->logger->error('[GENMAX] Failed to generate form extension class', [
                'entity' => $entity->getEntityName(),
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            throw new \RuntimeException(
                "Failed to generate form extension class {$entity->getEntityName()}: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Build template context with all variables needed for form generation
     */
    protected function buildTemplateContext(GeneratorEntity $entity): array
    {
        return [
            'entity' => $entity,
            'entityName' => $entity->getEntityName(),
            'formFields' => $this->getFormFields($entity),
            'formTypeImports' => $this->getFormTypeImports($entity),
            'namespace' => $this->paths['form_namespace'],
            'generatedNamespace' => $this->paths['form_generated_namespace'],
        ];
    }

    /**
     * Get properties to include in form
     */
    protected function getFormFields(GeneratorEntity $entity): array
    {
        $fields = [];

        foreach ($entity->getProperties() as $property) {
            // Skip if not shown in form
            if (!$property->isShowInForm()) {
                continue;
            }

            // Skip auto-generated fields
            if (in_array($property->getPropertyName(), ['id', 'createdAt', 'updatedAt', 'organization'], true)) {
                continue;
            }

            // Mark fields that are parent back-references (for conditional rendering)
            $isParentBackReference = $property->getRelationshipType() === 'ManyToOne' && $property->getInversedBy();

            $fields[] = [
                'name' => $property->getPropertyName(),
                'label' => $property->getPropertyLabel(),
                'type' => $this->determineFormType($property),
                'options' => $this->buildFormOptions($property, $entity),
                'isParentBackReference' => $isParentBackReference,
            ];
        }

        return $fields;
    }

    /**
     * Determine form type for a property
     */
    protected function determineFormType(GeneratorProperty $property): string
    {
        // Manual override
        if ($property->getFormType()) {
            return $property->getFormType();
        }

        // Relationship handling
        if ($relationshipType = $property->getRelationshipType()) {
            return match($relationshipType) {
                'ManyToOne', 'ManyToMany', 'OneToOne' => 'EntityType',
                'OneToMany' => 'CollectionType',
                default => 'TextType',
            };
        }

        // Enum handling
        if ($property->isEnum()) {
            return 'EnumType';
        }

        // Type mapping
        return match($property->getPropertyType()) {
            'string' => $property->getLength() > 255 ? 'TextareaType' : 'TextType',
            'text' => 'TextareaType',
            'integer', 'smallint', 'bigint' => 'IntegerType',
            'float', 'decimal' => 'NumberType',
            'boolean' => 'CheckboxType',
            'datetime', 'datetime_immutable' => 'DateTimeType',
            'date' => 'DateType',
            'time' => 'TimeType',
            'json' => 'TextareaType',
            default => 'TextType',
        };
    }

    /**
     * Build form options for a property
     */
    protected function buildFormOptions(GeneratorProperty $property, GeneratorEntity $entity): array
    {
        $options = [
            'label' => $property->getPropertyLabel(),
            'required' => $property->isFormRequired() ? true : !$property->isNullable(),
        ];

        // Help text
        if ($help = $property->getFormHelp()) {
            $options['help'] = $help;
        }

        // Read-only
        if ($property->isFormReadOnly()) {
            $options['disabled'] = true;
        }

        // Relationship-specific options
        if ($relationshipType = $property->getRelationshipType()) {
            $options = array_merge($options, $this->buildRelationshipOptions($property, $entity));
        }

        // Enum-specific options
        if ($property->isEnum()) {
            // Get enum class name: use explicit class if set, otherwise auto-generate from property name
            $enumClass = $property->getEnumClass();
            if (!$enumClass) {
                // Auto-generate enum class name using GenmaxExtension for proper naming
                $enumClassName = $this->genmaxExtension->getEnumClassName($property->getPropertyName());
                $enumClass = 'App\\Enum\\' . $enumClassName;
            }
            $options['class'] = $enumClass;
            $options['choice_label'] = 'getLabel';
        }

        // HTML attributes
        $attr = $property->getFormWidgetAttr() ?? [];

        // Always add base CSS class
        $attr['class'] = ($attr['class'] ?? '') . ' form-input-modern';
        $attr['class'] = trim($attr['class']);

        // Translated placeholder for text fields
        $formType = $this->determineFormType($property);
        if (in_array($formType, ['TextType', 'TextareaType'], true)) {
            $attr['placeholder'] = sprintf(
                'Enter %s',
                strtolower($property->getPropertyLabel())
            );
        }

        // Merge with existing attr (preserves data attributes from buildRelationshipOptions)
        $options['attr'] = array_merge($options['attr'] ?? [], $attr);

        // Label attributes
        if ($labelAttr = $property->getFormLabelAttr()) {
            $options['label_attr'] = $labelAttr;
        }

        // Row attributes
        if ($rowAttr = $property->getFormRowAttr()) {
            $options['row_attr'] = $rowAttr;
        }

        // Merge manual options (overrides everything)
        if ($manualOptions = $property->getFormOptions()) {
            $options = array_merge($options, $manualOptions);
        }

        return $options;
    }

    /**
     * Build relationship-specific options
     */
    protected function buildRelationshipOptions(GeneratorProperty $property, GeneratorEntity $entity): array
    {
        $relationshipType = $property->getRelationshipType();
        $targetEntity = $property->getTargetEntity();

        if (!$targetEntity) {
            return [];
        }

        $targetEntityName = basename(str_replace('\\', '/', $targetEntity));
        $entityRoute = $this->genmaxExtension->toSnakeCase($targetEntityName);

        // Convert short entity names to fully qualified class names
        $targetEntityClass = $targetEntity;
        if (!str_contains($targetEntityClass, '\\')) {
            $targetEntityClass = "App\\Entity\\{$targetEntityClass}";
        }

        $options = [
            'class' => $targetEntityClass,
            // Don't set choice_label - Symfony will automatically use __toString() if available
        ];

        // ManyToOne / ManyToMany / OneToOne
        if (in_array($relationshipType, ['ManyToOne', 'ManyToMany', 'OneToOne'], true)) {

            // Multiple (for ManyToMany only)
            if ($relationshipType === 'ManyToMany') {
                $options['multiple'] = true;
            }

            // Expanded (radio/checkboxes)
            if ($property->isFormExpanded()) {
                $options['expanded'] = true;
            } else {
                // Use relation-select controller for dropdown
                $searchRoute = $relationshipType === 'OneToOne'
                    ? "{$entityRoute}_api_search_unrelated"
                    : "{$entityRoute}_api_search";

                $attr = $options['attr'] ?? [];
                $attr['data-controller'] = 'relation-select';
                $attr['data-relation-select-entity-value'] = $targetEntityName;
                $attr['data-relation-select-route-value'] = $searchRoute;
                $attr['data-relation-select-add-route-value'] = "{$entityRoute}_new_modal";
                $attr['data-relation-select-multiple-value'] = $relationshipType === 'ManyToMany' ? 'true' : 'false';
                $attr['data-relation-select-one-to-one-value'] = $relationshipType === 'OneToOne' ? 'true' : 'false';

                // Translated placeholder
                $entityKey = $relationshipType === 'ManyToMany' ? 'plural' : 'singular';
                $placeholderKey = $relationshipType === 'ManyToMany'
                    ? 'Select one or more %s'
                    : 'Select %s';

                $attr['placeholder'] = sprintf($placeholderKey, strtolower($targetEntityName));

                $options['attr'] = $attr;
            }
        }

        // OneToMany - Collection
        if ($relationshipType === 'OneToMany') {
            $options = [
                'entry_type' => "App\\Form\\{$targetEntityName}Type",
                'entry_options' => [
                    'label' => false,
                    'exclude_parent' => true,  // Exclude parent back-reference to prevent circular references
                ],
                'allow_add' => $property->isCollectionAllowAdd(),
                'allow_delete' => $property->isCollectionAllowDelete(),
                'by_reference' => false,
                'prototype' => true,
                'attr' => [
                    'data-controller' => 'live-collection',
                    'data-live-collection-allow-add-value' => $property->isCollectionAllowAdd(),
                    'data-live-collection-allow-delete-value' => $property->isCollectionAllowDelete(),
                    'data-live-collection-max-items-value' => $property->getDtoNestedMaxItems() ?? 99,
                ],
                'label' => $property->getPropertyLabel(),
            ];

            // Add constraints
            $constraints = [];
            $constraints[] = "new \\Symfony\\Component\\Validator\\Constraints\\Count(['min' => 1])";

            if ($max = $property->getDtoNestedMaxItems()) {
                $constraints[] = "new \\Symfony\\Component\\Validator\\Constraints\\Count(['max' => {$max}])";
            }

            if (!empty($constraints)) {
                $options['constraints'] = $constraints;
            }
        }

        return $options;
    }

    /**
     * Get form type imports needed for this entity
     */
    protected function getFormTypeImports(GeneratorEntity $entity): array
    {
        $imports = [
            'Symfony\Component\Form\AbstractType',
            'Symfony\Component\Form\FormBuilderInterface',
            'Symfony\Component\OptionsResolver\OptionsResolver',
        ];

        // Collect all form types used
        $types = [];
        $entityClasses = [];
        $formTypeClasses = [];
        $enumClasses = [];

        foreach ($this->getFormFields($entity) as $field) {
            $types[$field['type']] = true;

            // Collect entity classes from EntityType fields
            if ($field['type'] === 'EntityType' && isset($field['options']['class'])) {
                $entityClasses[] = $field['options']['class'];
            }

            // Collect form types from CollectionType/OneToMany fields
            if (isset($field['options']['entry_type'])) {
                $formTypeClasses[] = $field['options']['entry_type'];
            }

            // Collect enum classes from EnumType fields
            if ($field['type'] === 'EnumType' && isset($field['options']['class'])) {
                $enumClasses[] = $field['options']['class'];
            }
        }

        // Add form type imports
        foreach (array_keys($types) as $type) {
            if ($type === 'EntityType') {
                $imports[] = 'Symfony\Bridge\Doctrine\Form\Type\EntityType';
            } elseif ($type === 'CollectionType') {
                $imports[] = 'Symfony\Component\Form\Extension\Core\Type\CollectionType';
            } elseif ($type === 'EnumType') {
                $imports[] = 'Symfony\Component\Form\Extension\Core\Type\EnumType';
            } else {
                $imports[] = "Symfony\\Component\\Form\\Extension\\Core\\Type\\{$type}";
            }
        }

        // Add entity class imports (for EntityType relationships)
        foreach (array_unique($entityClasses) as $entityClass) {
            // Convert short entity names to full class names
            if (!str_contains($entityClass, '\\')) {
                // Assume entities are in App\Entity namespace
                $entityClass = "App\\Entity\\{$entityClass}";
            }

            // Skip the main entity (already imported at top of file)
            $mainEntityClass = "App\\Entity\\{$entity->getEntityName()}";
            if ($entityClass === $mainEntityClass) {
                continue;
            }

            $imports[] = $entityClass;
        }

        // Add form type imports (for CollectionType/OneToMany relationships)
        // Note: These are already in the format App\Form\EntityType
        foreach (array_unique($formTypeClasses) as $formTypeClass) {
            // Skip if not a fully qualified class name
            if (!str_contains($formTypeClass, '\\')) {
                continue;
            }
            // Don't add to imports - these will be referenced with full namespace
        }

        // Add enum class imports
        foreach (array_unique($enumClasses) as $enumClass) {
            // Convert short enum names to full class names
            if (!str_contains($enumClass, '\\')) {
                // Assume enums are in App\Enum namespace
                $enumClass = "App\\Enum\\{$enumClass}";
            }
            $imports[] = $enumClass;
        }

        // Normalize backslashes - ensure only single backslashes
        // (Twig's PHP autoescape doubles them, so we need to handle this)
        $imports = array_map(function($import) {
            // Replace any double (or more) backslashes with single backslash
            return preg_replace('/\\\\+/', '\\', $import);
        }, $imports);

        return array_unique($imports);
    }

    /**
     * Generate translation keys for entity
     */
    protected function generateTranslationKeys(GeneratorEntity $entity): void
    {
        $entityName = $this->genmaxExtension->toSnakeCase($entity->getEntityName());
        $entityLabel = $entity->getEntityLabel();
        $pluralLabel = $entity->getPluralLabel();

        // TODO: Implement translation key generation to messages.en.yaml
        // This will be handled by a TranslationWriter service in the future

        $this->logger->info('[GENMAX] Translation keys to generate', [
            'entity' => $entityName,
            'keys' => [
                "entity.{$entityName}.singular" => strtolower($entityLabel),
                "entity.{$entityName}.plural" => strtolower($pluralLabel),
            ]
        ]);
    }
}
