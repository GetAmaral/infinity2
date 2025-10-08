#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Service\Generator\Csv\CsvParserService;

$projectDir = dirname(__DIR__);
$parser = new CsvParserService($projectDir);

echo "Parsing CSV files from: {$projectDir}/config/\n\n";

try {
    $result = $parser->parseAll();

    echo "Entities: " . count($result['entities']) . "\n";
    echo "Properties: " . count($result['properties']) . "\n\n";

    foreach ($result['entities'] as $entity) {
        echo "Entity: {$entity['entityName']}\n";
        echo "  - Label: {$entity['entityLabel']}\n";
        echo "  - Plural: {$entity['pluralLabel']}\n";
        echo "  - Icon: {$entity['icon']}\n";
        echo "\n";
    }

    echo "\nProperties:\n";
    foreach ($result['properties'] as $entityName => $entityProperties) {
        foreach ($entityProperties as $property) {
            echo "- {$property['entityName']}.{$property['propertyName']} ({$property['propertyType']})\n";
            echo "  Validation: " . json_encode($property['validationRules']) . "\n";
            echo "\n";
        }
    }

} catch (\Throwable $e) {
    echo "Error: {$e->getMessage()}\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
