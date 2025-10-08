<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Test;

use App\Service\Generator\Test\FixtureDataGenerator;
use App\Service\Generator\Csv\PropertyDefinitionDto;
use PHPUnit\Framework\TestCase;

class FixtureDataGeneratorTest extends TestCase
{
    private FixtureDataGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new FixtureDataGenerator();
    }

    public function testGetFakerMethodForEmail(): void
    {
        $property = $this->createProperty('email', 'string');

        $method = $this->generator->getFakerMethod($property);

        $this->assertEquals('email()', $method);
    }

    public function testGetFakerMethodForName(): void
    {
        $property = $this->createProperty('name', 'string');

        $method = $this->generator->getFakerMethod($property);

        $this->assertEquals('name()', $method);
    }

    public function testGetFakerMethodForPhone(): void
    {
        $property = $this->createProperty('phone', 'string');

        $method = $this->generator->getFakerMethod($property);

        $this->assertEquals('phoneNumber()', $method);
    }

    public function testGetFakerMethodForBoolean(): void
    {
        $property = $this->createProperty('active', 'boolean');

        $method = $this->generator->getFakerMethod($property);

        $this->assertEquals('boolean()', $method);
    }

    public function testGetFakerMethodForInteger(): void
    {
        $property = $this->createProperty('count', 'integer');

        $method = $this->generator->getFakerMethod($property);

        $this->assertEquals('numberBetween(1, 100)', $method);
    }

    public function testGetPhpValueForString(): void
    {
        $property = $this->createProperty('name', 'string');

        $value = $this->generator->getPhpValue($property);

        $this->assertStringContainsString('Test', $value);
    }

    public function testGetPhpValueForBoolean(): void
    {
        $property = $this->createProperty('active', 'boolean');

        $value = $this->generator->getPhpValue($property);

        $this->assertEquals('true', $value);
    }

    public function testGetPhpValueForInteger(): void
    {
        $property = $this->createProperty('count', 'integer');

        $value = $this->generator->getPhpValue($property);

        $this->assertEquals('123', $value);
    }

    private function createProperty(string $name, string $type): PropertyDefinitionDto
    {
        return PropertyDefinitionDto::fromArray([
            'entityName' => 'TestEntity',
            'propertyName' => $name,
            'propertyLabel' => ucfirst($name),
            'propertyType' => $type,
            'nullable' => false,
            'length' => null,
            'precision' => null,
            'scale' => null,
            'unique' => false,
            'defaultValue' => null,
            'relationshipType' => null,
            'targetEntity' => null,
            'inversedBy' => null,
            'mappedBy' => null,
            'cascade' => [],
            'orphanRemoval' => false,
            'fetch' => null,
            'orderBy' => [],
            'validationRules' => [],
            'validationMessage' => null,
            'formType' => 'TextType',
            'formOptions' => [],
            'formRequired' => true,
            'formReadOnly' => false,
            'formHelp' => null,
            'showInList' => true,
            'showInDetail' => true,
            'showInForm' => true,
            'sortable' => false,
            'searchable' => false,
            'filterable' => false,
            'apiReadable' => true,
            'apiWritable' => true,
            'apiGroups' => ['read', 'write'],
            'translationKey' => null,
            'formatPattern' => null,
            'fixtureType' => null,
            'fixtureOptions' => []
        ]);
    }
}
