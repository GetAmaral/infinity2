<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Csv;

use App\Service\Generator\Csv\PropertyDefinitionDto;
use PHPUnit\Framework\TestCase;

class PropertyDefinitionDtoTest extends TestCase
{
    public function testFromArrayCreatesDto(): void
    {
        $data = [
            'entityName' => 'Contact',
            'propertyName' => 'email',
            'propertyLabel' => 'Email Address',
            'propertyType' => 'string',
            'nullable' => false,
            'length' => 255,
            'precision' => null,
            'scale' => null,
            'unique' => true,
            'defaultValue' => null,
            'relationshipType' => null,
            'targetEntity' => null,
            'inversedBy' => null,
            'mappedBy' => null,
            'cascade' => [],
            'orphanRemoval' => false,
            'fetch' => null,
            'orderBy' => [],
            'validationRules' => ['NotBlank', 'Email'],
            'validationMessage' => 'Please enter a valid email',
            'formType' => 'EmailType',
            'formOptions' => ['attr' => ['placeholder' => 'email@example.com']],
            'formRequired' => true,
            'formReadOnly' => false,
            'formHelp' => 'Enter your email',
            'showInList' => true,
            'showInDetail' => true,
            'showInForm' => true,
            'sortable' => true,
            'searchable' => true,
            'filterable' => true,
            'apiReadable' => true,
            'apiWritable' => true,
            'apiGroups' => ['contact:read', 'contact:write'],
            'translationKey' => 'contact.email',
            'formatPattern' => null,
            'fixtureType' => 'email',
            'fixtureOptions' => ['unique' => true]
        ];

        $dto = PropertyDefinitionDto::fromArray($data);

        $this->assertEquals('Contact', $dto->entityName);
        $this->assertEquals('email', $dto->propertyName);
        $this->assertEquals('Email Address', $dto->propertyLabel);
        $this->assertEquals('string', $dto->propertyType);
        $this->assertFalse($dto->nullable);
        $this->assertEquals(255, $dto->length);
        $this->assertTrue($dto->unique);
        $this->assertEquals(['NotBlank', 'Email'], $dto->validationRules);
        $this->assertEquals('EmailType', $dto->formType);
    }

    public function testIsRelationshipReturnsFalseForScalar(): void
    {
        $dto = new PropertyDefinitionDto(
            entityName: 'Contact',
            propertyName: 'name',
            propertyLabel: 'Name',
            propertyType: 'string',
            nullable: false,
            length: 255,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: null,
            targetEntity: null,
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
            searchable: true,
            filterable: false,
            apiReadable: true,
            apiWritable: true,
            apiGroups: [],
            translationKey: null,
            formatPattern: null,
            fixtureType: null,
            fixtureOptions: []
        );

        $this->assertFalse($dto->isRelationship());
    }

    public function testIsRelationshipReturnsTrueForRelationship(): void
    {
        $dto = new PropertyDefinitionDto(
            entityName: 'Contact',
            propertyName: 'organization',
            propertyLabel: 'Organization',
            propertyType: 'string',
            nullable: false,
            length: null,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: 'ManyToOne',
            targetEntity: 'Organization',
            inversedBy: 'contacts',
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
            filterable: true,
            apiReadable: true,
            apiWritable: true,
            apiGroups: [],
            translationKey: null,
            formatPattern: null,
            fixtureType: null,
            fixtureOptions: []
        );

        $this->assertTrue($dto->isRelationship());
        $this->assertTrue($dto->isSingleRelationship());
        $this->assertFalse($dto->isCollection());
    }

    public function testIsCollectionReturnsTrueForOneToMany(): void
    {
        $dto = new PropertyDefinitionDto(
            entityName: 'Organization',
            propertyName: 'contacts',
            propertyLabel: 'Contacts',
            propertyType: 'string',
            nullable: false,
            length: null,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: 'OneToMany',
            targetEntity: 'Contact',
            inversedBy: null,
            mappedBy: 'organization',
            cascade: ['persist', 'remove'],
            orphanRemoval: true,
            fetch: null,
            orderBy: [],
            validationRules: [],
            validationMessage: null,
            formType: null,
            formOptions: [],
            formRequired: true,
            formReadOnly: false,
            formHelp: null,
            showInList: false,
            showInDetail: true,
            showInForm: true,
            sortable: false,
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

        $this->assertTrue($dto->isCollection());
        $this->assertFalse($dto->isSingleRelationship());
    }

    public function testGetPhpTypeReturnsCorrectTypes(): void
    {
        // String type
        $stringDto = new PropertyDefinitionDto(
            entityName: 'Test',
            propertyName: 'name',
            propertyLabel: 'Name',
            propertyType: 'string',
            nullable: false,
            length: 255,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: null,
            targetEntity: null,
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
            searchable: true,
            filterable: false,
            apiReadable: true,
            apiWritable: true,
            apiGroups: [],
            translationKey: null,
            formatPattern: null,
            fixtureType: null,
            fixtureOptions: []
        );
        $this->assertEquals('string', $stringDto->getPhpType());

        // Integer type
        $intDto = new PropertyDefinitionDto(
            entityName: 'Test',
            propertyName: 'count',
            propertyLabel: 'Count',
            propertyType: 'integer',
            nullable: false,
            length: null,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: null,
            targetEntity: null,
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
        $this->assertEquals('int', $intDto->getPhpType());

        // Boolean type
        $boolDto = new PropertyDefinitionDto(
            entityName: 'Test',
            propertyName: 'active',
            propertyLabel: 'Active',
            propertyType: 'boolean',
            nullable: false,
            length: null,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: null,
            targetEntity: null,
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
            sortable: false,
            searchable: false,
            filterable: true,
            apiReadable: true,
            apiWritable: true,
            apiGroups: [],
            translationKey: null,
            formatPattern: null,
            fixtureType: null,
            fixtureOptions: []
        );
        $this->assertEquals('bool', $boolDto->getPhpType());
    }

    public function testGetFormTypeReturnsDefaultTypes(): void
    {
        // Text type
        $textDto = new PropertyDefinitionDto(
            entityName: 'Test',
            propertyName: 'description',
            propertyLabel: 'Description',
            propertyType: 'text',
            nullable: true,
            length: null,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: null,
            targetEntity: null,
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
            formRequired: false,
            formReadOnly: false,
            formHelp: null,
            showInList: false,
            showInDetail: true,
            showInForm: true,
            sortable: false,
            searchable: true,
            filterable: false,
            apiReadable: true,
            apiWritable: true,
            apiGroups: [],
            translationKey: null,
            formatPattern: null,
            fixtureType: null,
            fixtureOptions: []
        );
        $this->assertEquals('TextareaType', $textDto->getFormType());

        // Boolean type
        $boolDto = new PropertyDefinitionDto(
            entityName: 'Test',
            propertyName: 'active',
            propertyLabel: 'Active',
            propertyType: 'boolean',
            nullable: false,
            length: null,
            precision: null,
            scale: null,
            unique: false,
            defaultValue: null,
            relationshipType: null,
            targetEntity: null,
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
            formRequired: false,
            formReadOnly: false,
            formHelp: null,
            showInList: true,
            showInDetail: true,
            showInForm: true,
            sortable: false,
            searchable: false,
            filterable: true,
            apiReadable: true,
            apiWritable: true,
            apiGroups: [],
            translationKey: null,
            formatPattern: null,
            fixtureType: null,
            fixtureOptions: []
        );
        $this->assertEquals('CheckboxType', $boolDto->getFormType());
    }

    public function testGetFormTypeReturnsCustomType(): void
    {
        $dto = new PropertyDefinitionDto(
            entityName: 'Contact',
            propertyName: 'email',
            propertyLabel: 'Email',
            propertyType: 'string',
            nullable: false,
            length: 255,
            precision: null,
            scale: null,
            unique: true,
            defaultValue: null,
            relationshipType: null,
            targetEntity: null,
            inversedBy: null,
            mappedBy: null,
            cascade: [],
            orphanRemoval: false,
            fetch: null,
            orderBy: [],
            validationRules: [],
            validationMessage: null,
            formType: 'EmailType',
            formOptions: [],
            formRequired: true,
            formReadOnly: false,
            formHelp: null,
            showInList: true,
            showInDetail: true,
            showInForm: true,
            sortable: true,
            searchable: true,
            filterable: false,
            apiReadable: true,
            apiWritable: true,
            apiGroups: [],
            translationKey: null,
            formatPattern: null,
            fixtureType: null,
            fixtureOptions: []
        );

        $this->assertEquals('EmailType', $dto->getFormType());
    }
}
