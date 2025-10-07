<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Csv;

use App\Service\Generator\Csv\CsvParserService;
use PHPUnit\Framework\TestCase;

class CsvParserServiceTest extends TestCase
{
    private CsvParserService $parser;

    protected function setUp(): void
    {
        $this->parser = new CsvParserService();
    }

    public function testParseEntityCsvReturnsArray(): void
    {
        $entities = $this->parser->parseEntityCsv();

        $this->assertIsArray($entities);
        $this->assertNotEmpty($entities);
        $this->assertArrayHasKey('entityName', $entities[0]);
        $this->assertEquals('Contact', $entities[0]['entityName']);
    }

    public function testParsePropertyCsvReturnsArray(): void
    {
        $properties = $this->parser->parsePropertyCsv();

        $this->assertIsArray($properties);
        $this->assertNotEmpty($properties);
        $this->assertArrayHasKey('Contact', $properties);
        $this->assertIsArray($properties['Contact']);
    }

    public function testParseAllLinksPropertiesToEntities(): void
    {
        $result = $this->parser->parseAll();

        $this->assertArrayHasKey('entities', $result);
        $this->assertArrayHasKey('properties', $result);
        $this->assertNotEmpty($result['entities']);

        $firstEntity = $result['entities'][0];
        $this->assertArrayHasKey('properties', $firstEntity);
        $this->assertIsArray($firstEntity['properties']);
    }

    public function testEntityDataNormalization(): void
    {
        $entities = $this->parser->parseEntityCsv();
        $entity = $entities[0];

        // Boolean fields
        $this->assertIsBool($entity['hasOrganization']);
        $this->assertIsBool($entity['apiEnabled']);
        $this->assertIsBool($entity['paginationEnabled']);
        $this->assertIsBool($entity['voterEnabled']);
        $this->assertIsBool($entity['testEnabled']);

        // Integer fields
        $this->assertIsInt($entity['itemsPerPage']);
        $this->assertIsInt($entity['menuOrder']);

        // Array fields
        $this->assertIsArray($entity['operations']);
        $this->assertIsArray($entity['searchableFields']);
        $this->assertIsArray($entity['filterableFields']);
        $this->assertIsArray($entity['voterAttributes']);
    }

    public function testPropertyDataNormalization(): void
    {
        $properties = $this->parser->parsePropertyCsv();
        $property = $properties['Contact'][0];

        // Boolean fields
        $this->assertIsBool($property['nullable']);
        $this->assertIsBool($property['unique']);
        $this->assertIsBool($property['formRequired']);
        $this->assertIsBool($property['formReadOnly']);
        $this->assertIsBool($property['showInList']);
        $this->assertIsBool($property['showInDetail']);
        $this->assertIsBool($property['showInForm']);
        $this->assertIsBool($property['sortable']);
        $this->assertIsBool($property['searchable']);
        $this->assertIsBool($property['filterable']);
        $this->assertIsBool($property['apiReadable']);
        $this->assertIsBool($property['apiWritable']);

        // Array fields
        $this->assertIsArray($property['validationRules']);
        $this->assertIsArray($property['apiGroups']);
    }

    public function testJsonFieldParsing(): void
    {
        $entities = $this->parser->parseEntityCsv();
        $entity = $entities[0];

        // Order field should be parsed as array from JSON
        $this->assertIsArray($entity['order']);
        $this->assertArrayHasKey('name', $entity['order']);
        $this->assertEquals('asc', $entity['order']['name']);
    }

    public function testCsvListParsing(): void
    {
        $entities = $this->parser->parseEntityCsv();
        $entity = $entities[0];

        // Operations should be parsed as array
        $this->assertContains('GetCollection', $entity['operations']);
        $this->assertContains('Get', $entity['operations']);
        $this->assertContains('Post', $entity['operations']);
        $this->assertContains('Put', $entity['operations']);
        $this->assertContains('Delete', $entity['operations']);
    }

    public function testBooleanParsing(): void
    {
        $entities = $this->parser->parseEntityCsv();
        $entity = $entities[0];

        // hasOrganization is 'true' in CSV, should be parsed as boolean
        $this->assertTrue($entity['hasOrganization']);
        $this->assertTrue($entity['apiEnabled']);
        $this->assertTrue($entity['paginationEnabled']);
    }
}
