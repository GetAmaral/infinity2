<?php

declare(strict_types=1);

namespace App\Service\Generator\Translation;

use App\Service\Generator\Csv\EntityDefinitionDto;
use Symfony\Component\Yaml\Yaml;
use Psr\Log\LoggerInterface;

class TranslationGenerator
{
    private const TRANSLATIONS_PATH = '/translations/messages.en.yaml';

    public function __construct(
        private readonly string $projectDir,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Generate translations for entities
     *
     * @param EntityDefinitionDto[] $entities
     */
    public function generate(array $entities): void
    {
        $translationsPath = $this->projectDir . self::TRANSLATIONS_PATH;

        // Ensure translations directory exists
        $translationsDir = dirname($translationsPath);
        if (!is_dir($translationsDir)) {
            mkdir($translationsDir, 0755, true);
        }

        // Load existing translations
        $existingTranslations = [];
        if (file_exists($translationsPath)) {
            $existingTranslations = Yaml::parseFile($translationsPath) ?? [];
        }

        $this->logger->info('Loading existing translations', [
            'path' => $translationsPath,
            'existing_count' => count($existingTranslations)
        ]);

        // Generate new translations
        $newTranslations = $this->generateTranslations($entities);

        // Merge (existing takes precedence to preserve custom translations)
        $merged = array_replace_recursive($newTranslations, $existingTranslations);

        // Write back
        $yaml = Yaml::dump($merged, 4, 2);
        file_put_contents($translationsPath, $yaml);

        $this->logger->info('Translations generated', [
            'new_count' => count($newTranslations),
            'merged_count' => count($merged),
            'entity_count' => count($entities)
        ]);
    }

    /**
     * Generate translation array for entities
     */
    private function generateTranslations(array $entities): array
    {
        $translations = [];

        // Common action translations
        $translations['action'] = [
            'create' => 'Create',
            'edit' => 'Edit',
            'delete' => 'Delete',
            'save' => 'Save',
            'back' => 'Back',
            'search' => 'Search',
            'label' => 'Actions',
            'confirm_delete' => 'Are you sure you want to delete this item?'
        ];

        // Common field translations
        $translations['field'] = [
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'id' => 'ID'
        ];

        foreach ($entities as $entity) {
            // Entity labels
            $translations[$entity->entityLabel] = $entity->entityLabel;
            $translations[$entity->pluralLabel] = $entity->pluralLabel;

            // Field labels
            foreach ($entity->properties as $property) {
                // Use humanized property name if label not specified
                $label = $property->propertyLabel ?: $this->humanize($property->propertyName);
                $translations[$property->propertyLabel] = $label;

                // Help text
                if ($property->formHelp) {
                    $translations[$property->formHelp] = $property->formHelp;
                }
            }
        }

        return $translations;
    }

    /**
     * Convert camelCase to "Human Readable"
     */
    private function humanize(string $text): string
    {
        // Insert space before uppercase letters
        $humanized = preg_replace('/([a-z])([A-Z])/', '$1 $2', $text);

        // Capitalize first letter
        return ucfirst($humanized);
    }
}
