<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Csv;

use App\Service\Generator\Csv\EntityDefinitionDto;
use App\Service\Generator\Csv\PropertyDefinitionDto;
use PHPUnit\Framework\TestCase;

class EntityDefinitionDtoTest extends TestCase
{
    public function testFromArrayCreatesDto(): void
    {
        $data = [
            'entityName' => 'Contact',
            'entityLabel' => 'Contact',
            'pluralLabel' => 'Contacts',
            'icon' => 'bi-person',
            'description' => 'Manages customer contacts',
            'hasOrganization' => true,
            'apiEnabled' => true,
            'operations' => ['GetCollection', 'Get', 'Post', 'Put', 'Delete'],
            'security' => "is_granted('ROLE_USER')",
            'normalizationContext' => 'contact:read,audit:read',
            'denormalizationContext' => 'contact:write',
            'paginationEnabled' => true,
            'itemsPerPage' => 30,
            'order' => ['name' => 'asc'],
            'searchableFields' => ['name', 'email', 'phone'],
            'filterableFields' => ['status', 'active'],
            'voterEnabled' => true,
            'voterAttributes' => ['VIEW', 'EDIT', 'DELETE'],
            'formTheme' => 'bootstrap_5_layout.html.twig',
            'indexTemplate' => '',
            'formTemplate' => '',
            'showTemplate' => '',
            'menuGroup' => 'CRM',
            'menuOrder' => 10,
            'testEnabled' => true,
            'properties' => []
        ];

        $dto = EntityDefinitionDto::fromArray($data);

        $this->assertEquals('Contact', $dto->entityName);
        $this->assertEquals('Contact', $dto->entityLabel);
        $this->assertEquals('Contacts', $dto->pluralLabel);
        $this->assertEquals('bi-person', $dto->icon);
        $this->assertTrue($dto->hasOrganization);
        $this->assertTrue($dto->apiEnabled);
        $this->assertEquals(['GetCollection', 'Get', 'Post', 'Put', 'Delete'], $dto->operations);
        $this->assertEquals(30, $dto->itemsPerPage);
        $this->assertEquals('CRM', $dto->menuGroup);
    }

    public function testGetLowercaseName(): void
    {
        $dto = new EntityDefinitionDto(
            entityName: 'Contact',
            entityLabel: 'Contact',
            pluralLabel: 'Contacts',
            icon: 'bi-person',
            description: '',
            hasOrganization: true,
            apiEnabled: true,
            operations: [],
            security: '',
            normalizationContext: '',
            denormalizationContext: '',
            paginationEnabled: true,
            itemsPerPage: 30,
            order: [],
            searchableFields: [],
            filterableFields: [],
            voterEnabled: true,
            voterAttributes: [],
            formTheme: '',
            indexTemplate: '',
            formTemplate: '',
            showTemplate: '',
            menuGroup: '',
            menuOrder: 0,
            testEnabled: true,
            properties: []
        );

        $this->assertEquals('contact', $dto->getLowercaseName());
    }

    public function testGetSnakeCaseName(): void
    {
        $dto = new EntityDefinitionDto(
            entityName: 'ContactPerson',
            entityLabel: 'Contact Person',
            pluralLabel: 'Contact Persons',
            icon: 'bi-person',
            description: '',
            hasOrganization: true,
            apiEnabled: true,
            operations: [],
            security: '',
            normalizationContext: '',
            denormalizationContext: '',
            paginationEnabled: true,
            itemsPerPage: 30,
            order: [],
            searchableFields: [],
            filterableFields: [],
            voterEnabled: true,
            voterAttributes: [],
            formTheme: '',
            indexTemplate: '',
            formTemplate: '',
            showTemplate: '',
            menuGroup: '',
            menuOrder: 0,
            testEnabled: true,
            properties: []
        );

        $this->assertEquals('contact_person', $dto->getSnakeCaseName());
    }

    public function testHasRelationshipsReturnsFalseWhenNoRelationships(): void
    {
        $dto = new EntityDefinitionDto(
            entityName: 'Contact',
            entityLabel: 'Contact',
            pluralLabel: 'Contacts',
            icon: 'bi-person',
            description: '',
            hasOrganization: true,
            apiEnabled: true,
            operations: [],
            security: '',
            normalizationContext: '',
            denormalizationContext: '',
            paginationEnabled: true,
            itemsPerPage: 30,
            order: [],
            searchableFields: [],
            filterableFields: [],
            voterEnabled: true,
            voterAttributes: [],
            formTheme: '',
            indexTemplate: '',
            formTemplate: '',
            showTemplate: '',
            menuGroup: '',
            menuOrder: 0,
            testEnabled: true,
            properties: []
        );

        $this->assertFalse($dto->hasRelationships());
    }

    public function testGetScalarPropertiesReturnsOnlyScalars(): void
    {
        $scalarProp = $this->createProperty('name', null);
        $relationProp = $this->createProperty('organization', 'ManyToOne');

        $dto = new EntityDefinitionDto(
            entityName: 'Contact',
            entityLabel: 'Contact',
            pluralLabel: 'Contacts',
            icon: 'bi-person',
            description: '',
            hasOrganization: true,
            apiEnabled: true,
            operations: [],
            security: '',
            normalizationContext: '',
            denormalizationContext: '',
            paginationEnabled: true,
            itemsPerPage: 30,
            order: [],
            searchableFields: [],
            filterableFields: [],
            voterEnabled: true,
            voterAttributes: [],
            formTheme: '',
            indexTemplate: '',
            formTemplate: '',
            showTemplate: '',
            menuGroup: '',
            menuOrder: 0,
            testEnabled: true,
            properties: [$scalarProp, $relationProp]
        );

        $scalars = $dto->getScalarProperties();
        $this->assertCount(1, $scalars);
        $this->assertEquals('name', reset($scalars)->propertyName);
    }

    public function testGetRelationshipPropertiesReturnsOnlyRelationships(): void
    {
        $scalarProp = $this->createProperty('name', null);
        $relationProp = $this->createProperty('organization', 'ManyToOne');

        $dto = new EntityDefinitionDto(
            entityName: 'Contact',
            entityLabel: 'Contact',
            pluralLabel: 'Contacts',
            icon: 'bi-person',
            description: '',
            hasOrganization: true,
            apiEnabled: true,
            operations: [],
            security: '',
            normalizationContext: '',
            denormalizationContext: '',
            paginationEnabled: true,
            itemsPerPage: 30,
            order: [],
            searchableFields: [],
            filterableFields: [],
            voterEnabled: true,
            voterAttributes: [],
            formTheme: '',
            indexTemplate: '',
            formTemplate: '',
            showTemplate: '',
            menuGroup: '',
            menuOrder: 0,
            testEnabled: true,
            properties: [$scalarProp, $relationProp]
        );

        $relationships = $dto->getRelationshipProperties();
        $this->assertCount(1, $relationships);
        $this->assertEquals('organization', reset($relationships)->propertyName);
    }

    private function createProperty(string $name, ?string $relationshipType): PropertyDefinitionDto
    {
        return new PropertyDefinitionDto(
            entityName: 'Contact',
            propertyName: $name,
            propertyLabel: $name,
            propertyType: 'string',
            nullable: false,
            length: 255,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: $relationshipType,
            targetEntity: $relationshipType !== null ? 'Organization' : null,
            inversedBy: null,
            mappedBy: null,
            cascade: [],
            orphanRemoval: false,
            fetch: null,
            orderBy: [],
            validationRules: [],
            validationMessage: null,
            formType: null,
            formOptions: [],
            formRequired: true,
            formReadOnly: false,
            formHelp: null,
            showInList: true,
            showInDetail: true,
            showInForm: true,
            sortable: true,
            searchable: false,
            filterable: false,
            apiReadable: true,
            apiWritable: true,
            apiGroups: [],
            translationKey: null,
            formatPattern: null,
            fixtureType: null,
            fixtureOptions: []
        );
    }
}
