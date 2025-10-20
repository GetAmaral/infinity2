#!/usr/bin/env php
<?php

/**
 * Script to add comprehensive Country entity properties
 * Following ISO standards and CRM best practices 2025
 */

require __DIR__ . '/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use App\Entity\Generator\GeneratorEntity;
use App\Entity\Generator\GeneratorProperty;
use Doctrine\ORM\EntityManagerInterface;

// Load environment
(new Dotenv())->bootEnv(__DIR__ . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$em = $container->get('doctrine')->getManager();

// Get Country entity
$countryEntity = $em->getRepository(GeneratorEntity::class)->findOneBy(['entityName' => 'Country']);

if (!$countryEntity) {
    echo "ERROR: Country entity not found!\n";
    exit(1);
}

echo "Found Country entity: {$countryEntity->getId()}\n\n";

// Define all properties to add
$properties = [
    // CRITICAL PROPERTIES
    [
        'name' => 'iso2',
        'label' => 'ISO 3166-1 Alpha-2',
        'type' => 'string',
        'length' => 2,
        'nullable' => false,
        'unique' => true,
        'indexed' => true,
        'order' => 1,
        'validation' => [
            'NotBlank' => [],
            'Length' => ['min' => 2, 'max' => 2],
            'Regex' => ['pattern' => '/^[A-Z]{2}$/']
        ],
        'filterStrategy' => 'exact',
        'filterSearchable' => true,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Two-letter country code (ISO 3166-1 alpha-2)',
        'apiExample' => 'US'
    ],
    [
        'name' => 'iso3',
        'label' => 'ISO 3166-1 Alpha-3',
        'type' => 'string',
        'length' => 3,
        'nullable' => false,
        'unique' => true,
        'indexed' => true,
        'order' => 2,
        'validation' => [
            'NotBlank' => [],
            'Length' => ['min' => 3, 'max' => 3],
            'Regex' => ['pattern' => '/^[A-Z]{3}$/']
        ],
        'filterStrategy' => 'exact',
        'filterSearchable' => true,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Three-letter country code (ISO 3166-1 alpha-3)',
        'apiExample' => 'USA'
    ],
    [
        'name' => 'numericCode',
        'label' => 'ISO Numeric Code',
        'type' => 'string',
        'length' => 3,
        'nullable' => false,
        'unique' => true,
        'indexed' => true,
        'order' => 3,
        'validation' => [
            'NotBlank' => [],
            'Length' => ['min' => 3, 'max' => 3],
            'Regex' => ['pattern' => '/^\d{3}$/']
        ],
        'filterStrategy' => 'exact',
        'filterSearchable' => true,
        'filterOrderable' => false,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Three-digit country code (ISO 3166-1 numeric)',
        'apiExample' => '840'
    ],
    [
        'name' => 'currencyCode',
        'label' => 'Currency Code',
        'type' => 'string',
        'length' => 3,
        'nullable' => false,
        'unique' => false,
        'indexed' => true,
        'order' => 4,
        'validation' => [
            'NotBlank' => [],
            'Length' => ['min' => 3, 'max' => 3],
            'Regex' => ['pattern' => '/^[A-Z]{3}$/']
        ],
        'filterStrategy' => 'exact',
        'filterSearchable' => true,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'ISO 4217 currency code',
        'apiExample' => 'USD'
    ],
    [
        'name' => 'phoneCode',
        'label' => 'Phone Code',
        'type' => 'string',
        'length' => 10,
        'nullable' => false,
        'unique' => false,
        'indexed' => false,
        'order' => 5,
        'validation' => [
            'NotBlank' => [],
            'Length' => ['max' => 10],
            'Regex' => ['pattern' => '/^\+\d{1,4}$/']
        ],
        'filterStrategy' => 'partial',
        'filterSearchable' => true,
        'filterOrderable' => false,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'International phone dialing code',
        'apiExample' => '+1'
    ],
    [
        'name' => 'active',
        'label' => 'Active',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'true',
        'indexed' => true,
        'order' => 6,
        'filterBoolean' => true,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Whether country is active in the system',
        'apiExample' => true
    ],
    [
        'name' => 'continent',
        'label' => 'Continent',
        'type' => 'string',
        'length' => 50,
        'nullable' => false,
        'indexed' => true,
        'order' => 7,
        'validation' => [
            'NotBlank' => [],
            'Length' => ['max' => 50]
        ],
        'filterStrategy' => 'exact',
        'filterSearchable' => false,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Continent name',
        'apiExample' => 'North America'
    ],
    // HIGH PRIORITY PROPERTIES
    [
        'name' => 'capital',
        'label' => 'Capital City',
        'type' => 'string',
        'length' => 100,
        'nullable' => true,
        'order' => 8,
        'validation' => [
            'Length' => ['max' => 100]
        ],
        'filterStrategy' => 'partial',
        'filterSearchable' => true,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Capital city name',
        'apiExample' => 'Washington, D.C.'
    ],
    [
        'name' => 'currencySymbol',
        'label' => 'Currency Symbol',
        'type' => 'string',
        'length' => 5,
        'nullable' => true,
        'order' => 9,
        'validation' => [
            'Length' => ['max' => 5]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Currency display symbol',
        'apiExample' => '$'
    ],
    [
        'name' => 'euMember',
        'label' => 'EU Member',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'false',
        'order' => 10,
        'filterBoolean' => true,
        'filterOrderable' => false,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'European Union member state',
        'apiExample' => false
    ],
    [
        'name' => 'region',
        'label' => 'Region',
        'type' => 'string',
        'length' => 100,
        'nullable' => true,
        'order' => 11,
        'validation' => [
            'Length' => ['max' => 100]
        ],
        'filterStrategy' => 'exact',
        'filterSearchable' => false,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'UN geographic region',
        'apiExample' => 'Northern America'
    ],
    [
        'name' => 'nativeName',
        'label' => 'Native Name',
        'type' => 'string',
        'length' => 100,
        'nullable' => true,
        'order' => 12,
        'validation' => [
            'Length' => ['max' => 100]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Country name in native language',
        'apiExample' => 'United States of America'
    ],
    [
        'name' => 'officialName',
        'label' => 'Official Name',
        'type' => 'string',
        'length' => 200,
        'nullable' => true,
        'order' => 13,
        'validation' => [
            'Length' => ['max' => 200]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Official full country name',
        'apiExample' => 'United States of America'
    ],
    // MEDIUM PRIORITY PROPERTIES
    [
        'name' => 'subregion',
        'label' => 'Subregion',
        'type' => 'string',
        'length' => 100,
        'nullable' => true,
        'order' => 14,
        'validation' => [
            'Length' => ['max' => 100]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'UN geographic subregion',
        'apiExample' => 'Northern America'
    ],
    [
        'name' => 'latitude',
        'label' => 'Latitude',
        'type' => 'decimal',
        'precision' => 10,
        'scale' => 7,
        'nullable' => true,
        'order' => 15,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Geographic latitude',
        'apiExample' => 38.9072
    ],
    [
        'name' => 'longitude',
        'label' => 'Longitude',
        'type' => 'decimal',
        'precision' => 10,
        'scale' => 7,
        'nullable' => true,
        'order' => 16,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Geographic longitude',
        'apiExample' => -77.0369
    ],
    [
        'name' => 'timezones',
        'label' => 'Timezones',
        'type' => 'json',
        'nullable' => true,
        'order' => 17,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Array of timezone identifiers',
        'apiExample' => '["America/New_York"]'
    ],
    [
        'name' => 'languages',
        'label' => 'Languages',
        'type' => 'json',
        'nullable' => true,
        'order' => 18,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Array of ISO 639 language codes',
        'apiExample' => '["en"]'
    ],
    [
        'name' => 'tld',
        'label' => 'TLD',
        'type' => 'string',
        'length' => 10,
        'nullable' => true,
        'order' => 19,
        'validation' => [
            'Length' => ['max' => 10]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Country code top-level domain',
        'apiExample' => '.us'
    ],
    [
        'name' => 'nationalityName',
        'label' => 'Nationality',
        'type' => 'string',
        'length' => 100,
        'nullable' => true,
        'order' => 20,
        'validation' => [
            'Length' => ['max' => 100]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Demonym/nationality name',
        'apiExample' => 'American'
    ],
    [
        'name' => 'availableForShipping',
        'label' => 'Available for Shipping',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'true',
        'order' => 21,
        'filterBoolean' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Country available for shipping operations',
        'apiExample' => true
    ],
    [
        'name' => 'availableForBilling',
        'label' => 'Available for Billing',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'true',
        'order' => 22,
        'filterBoolean' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Country available for billing operations',
        'apiExample' => true
    ],
    [
        'name' => 'schengenMember',
        'label' => 'Schengen Member',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'false',
        'order' => 23,
        'filterBoolean' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Schengen Area member state',
        'apiExample' => false
    ],
    [
        'name' => 'oecdMember',
        'label' => 'OECD Member',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'false',
        'order' => 24,
        'filterBoolean' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'OECD member country',
        'apiExample' => false
    ],
    [
        'name' => 'dataResidencyRequired',
        'label' => 'Data Residency Required',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'false',
        'order' => 25,
        'filterBoolean' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Data localization laws in effect',
        'apiExample' => false
    ],
    [
        'name' => 'postalCodeFormat',
        'label' => 'Postal Code Format',
        'type' => 'string',
        'length' => 100,
        'nullable' => true,
        'order' => 26,
        'validation' => [
            'Length' => ['max' => 100]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Postal code regex validation pattern',
        'apiExample' => '^\\d{5}(-\\d{4})?$'
    ],
    [
        'name' => 'postalCodeRequired',
        'label' => 'Postal Code Required',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'true',
        'order' => 27,
        'filterBoolean' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Whether postal code is mandatory',
        'apiExample' => true
    ],
    [
        'name' => 'addressFormat',
        'label' => 'Address Format',
        'type' => 'text',
        'nullable' => true,
        'order' => 28,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Address format template',
        'apiExample' => '{street}\\n{city}, {region} {postalCode}'
    ],
    [
        'name' => 'taxIdRequired',
        'label' => 'Tax ID Required',
        'type' => 'boolean',
        'nullable' => false,
        'defaultValue' => 'false',
        'order' => 29,
        'filterBoolean' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Tax ID/VAT required for transactions',
        'apiExample' => false
    ],
    // LOW PRIORITY PROPERTIES
    [
        'name' => 'population',
        'label' => 'Population',
        'type' => 'integer',
        'nullable' => true,
        'order' => 30,
        'filterNumericRange' => true,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Current population estimate',
        'apiExample' => 331900000
    ],
    [
        'name' => 'area',
        'label' => 'Area (km²)',
        'type' => 'decimal',
        'precision' => 12,
        'scale' => 2,
        'nullable' => true,
        'order' => 31,
        'filterNumericRange' => true,
        'filterOrderable' => true,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Total area in square kilometers',
        'apiExample' => 9833520.00
    ],
    [
        'name' => 'unMemberSince',
        'label' => 'UN Member Since',
        'type' => 'integer',
        'nullable' => true,
        'order' => 32,
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Year joined United Nations',
        'apiExample' => 1945
    ],
    [
        'name' => 'flagEmoji',
        'label' => 'Flag Emoji',
        'type' => 'string',
        'length' => 10,
        'nullable' => true,
        'order' => 33,
        'validation' => [
            'Length' => ['max' => 10]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'Unicode flag emoji',
        'apiExample' => '🇺🇸'
    ],
    [
        'name' => 'flagSvgUrl',
        'label' => 'Flag SVG URL',
        'type' => 'string',
        'length' => 255,
        'nullable' => true,
        'order' => 34,
        'validation' => [
            'Length' => ['max' => 255]
        ],
        'apiReadable' => true,
        'apiWritable' => true,
        'apiDescription' => 'URL to official flag SVG image',
        'apiExample' => 'https://flagcdn.com/us.svg'
    ],
];

// First, remove the old dialingCode property
echo "Removing old 'dialingCode' property...\n";
$oldDialingCode = $em->getRepository(GeneratorProperty::class)->findOneBy([
    'entity' => $countryEntity,
    'propertyName' => 'dialingCode'
]);
if ($oldDialingCode) {
    $em->remove($oldDialingCode);
    $em->flush();
    echo "  - Removed dialingCode property\n";
}

// Update name property to have better configuration
echo "\nUpdating 'name' property...\n";
$nameProperty = $em->getRepository(GeneratorProperty::class)->findOneBy([
    'entity' => $countryEntity,
    'propertyName' => 'name'
]);
if ($nameProperty) {
    $nameProperty->setLength(100);
    $nameProperty->setUnique(true);
    $nameProperty->setIndexed(true);
    $nameProperty->setPropertyOrder(0);
    $nameProperty->setFilterStrategy('partial');
    $nameProperty->setFilterSearchable(true);
    $nameProperty->setFilterOrderable(true);
    $nameProperty->setApiReadable(true);
    $nameProperty->setApiWritable(true);
    $nameProperty->setApiDescription('Country name');
    $nameProperty->setApiExample('United States');
    $nameProperty->setValidationRules([
        'NotBlank' => [],
        'Length' => ['min' => 2, 'max' => 100]
    ]);
    $em->persist($nameProperty);
    echo "  - Updated name property\n";
}

// Add all new properties
echo "\nAdding new properties:\n";
$addedCount = 0;
$skippedCount = 0;

foreach ($properties as $propDef) {
    // Check if property already exists
    $existing = $em->getRepository(GeneratorProperty::class)->findOneBy([
        'entity' => $countryEntity,
        'propertyName' => $propDef['name']
    ]);

    if ($existing) {
        echo "  - SKIP: {$propDef['name']} (already exists)\n";
        $skippedCount++;
        continue;
    }

    $property = new GeneratorProperty();
    $property->setEntity($countryEntity);
    $property->setPropertyName($propDef['name']);
    $property->setPropertyLabel($propDef['label']);
    $property->setPropertyType($propDef['type']);
    $property->setPropertyOrder($propDef['order']);

    if (isset($propDef['length'])) {
        $property->setLength($propDef['length']);
    }
    if (isset($propDef['precision'])) {
        $property->setPrecision($propDef['precision']);
    }
    if (isset($propDef['scale'])) {
        $property->setScale($propDef['scale']);
    }
    if (isset($propDef['nullable'])) {
        $property->setNullable($propDef['nullable']);
    }
    if (isset($propDef['unique'])) {
        $property->setUnique($propDef['unique']);
    }
    if (isset($propDef['indexed'])) {
        $property->setIndexed($propDef['indexed']);
    }
    if (isset($propDef['defaultValue'])) {
        $property->setDefaultValue($propDef['defaultValue']);
    }
    if (isset($propDef['validation'])) {
        $property->setValidationRules($propDef['validation']);
    }
    if (isset($propDef['filterStrategy'])) {
        $property->setFilterStrategy($propDef['filterStrategy']);
    }
    if (isset($propDef['filterSearchable'])) {
        $property->setFilterSearchable($propDef['filterSearchable']);
    }
    if (isset($propDef['filterOrderable'])) {
        $property->setFilterOrderable($propDef['filterOrderable']);
    }
    if (isset($propDef['filterBoolean'])) {
        $property->setFilterBoolean($propDef['filterBoolean']);
    }
    if (isset($propDef['filterNumericRange'])) {
        $property->setFilterNumericRange($propDef['filterNumericRange']);
    }
    if (isset($propDef['apiReadable'])) {
        $property->setApiReadable($propDef['apiReadable']);
    }
    if (isset($propDef['apiWritable'])) {
        $property->setApiWritable($propDef['apiWritable']);
    }
    if (isset($propDef['apiDescription'])) {
        $property->setApiDescription($propDef['apiDescription']);
    }
    if (isset($propDef['apiExample'])) {
        $property->setApiExample($propDef['apiExample']);
    }

    $em->persist($property);
    echo "  + ADDED: {$propDef['name']} ({$propDef['type']})\n";
    $addedCount++;
}

echo "\nSaving all properties...\n";
$em->flush();

echo "\n" . str_repeat("=", 60) . "\n";
echo "SUMMARY\n";
echo str_repeat("=", 60) . "\n";
echo "Properties added: $addedCount\n";
echo "Properties skipped: $skippedCount\n";
echo "Total properties defined: " . ($addedCount + $skippedCount + 1) . " (including name)\n";
echo "\nNext steps:\n";
echo "1. Run: docker-compose exec app php bin/console genmax:generate Country\n";
echo "2. Run: docker-compose exec app php bin/console make:migration\n";
echo "3. Review migration file\n";
echo "4. Run: docker-compose exec app php bin/console doctrine:migrations:migrate\n";
echo "\n";
