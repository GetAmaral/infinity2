<?php

declare(strict_types=1);

namespace App\Tests\Service\Generator\Csv;

use App\Service\Generator\Csv\CsvValidatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class CsvValidatorServiceTest extends TestCase
{
    private CsvValidatorService $validator;

    protected function setUp(): void
    {
        $this->validator = new CsvValidatorService(new NullLogger());
    }

    public function testValidateAllPassesForValidData(): void
    {
        $entities = [
            [
                'entityName' => 'Contact',
                'entityLabel' => 'Contact',
                'pluralLabel' => 'Contacts',
                'icon' => 'bi-person',
                'description' => 'Test',
                'hasOrganization' => true,
                'apiEnabled' => true,
                'operations' => ['Get', 'Post'],
                'security' => "is_granted('ROLE_USER')",
                'normalizationContext' => 'contact:read',
                'denormalizationContext' => 'contact:write',
                'paginationEnabled' => true,
                'itemsPerPage' => 30,
                'order' => [],
                'searchableFields' => [],
                'filterableFields' => [],
                'voterEnabled' => true,
                'voterAttributes' => ['VIEW', 'EDIT'],
                'formTheme' => 'bootstrap_5_layout.html.twig',
                'indexTemplate' => '',
                'formTemplate' => '',
                'showTemplate' => '',
                'menuGroup' => 'CRM',
                'menuOrder' => 10,
                'testEnabled' => true
            ]
        ];

        $properties = [
            'Contact' => [
                [
                    'entityName' => 'Contact',
                    'propertyName' => 'name',
                    'propertyLabel' => 'Name',
                    'propertyType' => 'string',
                    'nullable' => false,
                    'length' => 255,
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
                    'validationRules' => ['NotBlank'],
                    'validationMessage' => null,
                    'formType' => 'TextType',
                    'formOptions' => [],
                    'formRequired' => true,
                    'formReadOnly' => false,
                    'formHelp' => null,
                    'showInList' => true,
                    'showInDetail' => true,
                    'showInForm' => true,
                    'sortable' => true,
                    'searchable' => true,
                    'filterable' => false,
                    'apiReadable' => true,
                    'apiWritable' => true,
                    'apiGroups' => [],
                    'translationKey' => null,
                    'formatPattern' => null,
                    'fixtureType' => null,
                    'fixtureOptions' => []
                ]
            ]
        ];

        $result = $this->validator->validateAll($entities, $properties);

        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateEntityFailsForInvalidEntityName(): void
    {
        $entities = [
            [
                'entityName' => 'contact',  // Should be PascalCase
                'entityLabel' => 'Contact',
                'pluralLabel' => 'Contacts',
                'icon' => 'bi-person',
                'description' => '',
                'hasOrganization' => false,
                'apiEnabled' => true,
                'operations' => [],
                'security' => '',
                'normalizationContext' => '',
                'denormalizationContext' => '',
                'paginationEnabled' => true,
                'itemsPerPage' => 30,
                'order' => [],
                'searchableFields' => [],
                'filterableFields' => [],
                'voterEnabled' => true,
                'voterAttributes' => [],
                'formTheme' => '',
                'indexTemplate' => '',
                'formTemplate' => '',
                'showTemplate' => '',
                'menuGroup' => '',
                'menuOrder' => 0,
                'testEnabled' => true
            ]
        ];

        $result = $this->validator->validateAll($entities, []);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('PascalCase', $result['errors'][0]);
    }

    public function testValidateEntityFailsForInvalidIcon(): void
    {
        $entities = [
            [
                'entityName' => 'Contact',
                'entityLabel' => 'Contact',
                'pluralLabel' => 'Contacts',
                'icon' => 'person',  // Should start with 'bi-'
                'description' => '',
                'hasOrganization' => false,
                'apiEnabled' => true,
                'operations' => [],
                'security' => '',
                'normalizationContext' => '',
                'denormalizationContext' => '',
                'paginationEnabled' => true,
                'itemsPerPage' => 30,
                'order' => [],
                'searchableFields' => [],
                'filterableFields' => [],
                'voterEnabled' => true,
                'voterAttributes' => [],
                'formTheme' => '',
                'indexTemplate' => '',
                'formTemplate' => '',
                'showTemplate' => '',
                'menuGroup' => '',
                'menuOrder' => 0,
                'testEnabled' => true
            ]
        ];

        $result = $this->validator->validateAll($entities, []);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString("must start with 'bi-'", $result['errors'][0]);
    }

    public function testValidatePropertyFailsForInvalidPropertyName(): void
    {
        $entities = [
            [
                'entityName' => 'Contact',
                'entityLabel' => 'Contact',
                'pluralLabel' => 'Contacts',
                'icon' => 'bi-person',
                'description' => '',
                'hasOrganization' => false,
                'apiEnabled' => true,
                'operations' => [],
                'security' => '',
                'normalizationContext' => '',
                'denormalizationContext' => '',
                'paginationEnabled' => true,
                'itemsPerPage' => 30,
                'order' => [],
                'searchableFields' => [],
                'filterableFields' => [],
                'voterEnabled' => true,
                'voterAttributes' => [],
                'formTheme' => '',
                'indexTemplate' => '',
                'formTemplate' => '',
                'showTemplate' => '',
                'menuGroup' => '',
                'menuOrder' => 0,
                'testEnabled' => true
            ]
        ];

        $properties = [
            'Contact' => [
                [
                    'entityName' => 'Contact',
                    'propertyName' => 'FullName',  // Should be camelCase
                    'propertyLabel' => 'Full Name',
                    'propertyType' => 'string',
                    'nullable' => false,
                    'length' => 255,
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
                    'formType' => null,
                    'formOptions' => [],
                    'formRequired' => true,
                    'formReadOnly' => false,
                    'formHelp' => null,
                    'showInList' => true,
                    'showInDetail' => true,
                    'showInForm' => true,
                    'sortable' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'apiReadable' => true,
                    'apiWritable' => true,
                    'apiGroups' => [],
                    'translationKey' => null,
                    'formatPattern' => null,
                    'fixtureType' => null,
                    'fixtureOptions' => []
                ]
            ]
        ];

        $result = $this->validator->validateAll($entities, $properties);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('camelCase', $result['errors'][0]);
    }

    public function testValidatePropertyFailsForStringWithoutLength(): void
    {
        $entities = [
            [
                'entityName' => 'Contact',
                'entityLabel' => 'Contact',
                'pluralLabel' => 'Contacts',
                'icon' => 'bi-person',
                'description' => '',
                'hasOrganization' => false,
                'apiEnabled' => true,
                'operations' => [],
                'security' => '',
                'normalizationContext' => '',
                'denormalizationContext' => '',
                'paginationEnabled' => true,
                'itemsPerPage' => 30,
                'order' => [],
                'searchableFields' => [],
                'filterableFields' => [],
                'voterEnabled' => true,
                'voterAttributes' => [],
                'formTheme' => '',
                'indexTemplate' => '',
                'formTemplate' => '',
                'showTemplate' => '',
                'menuGroup' => '',
                'menuOrder' => 0,
                'testEnabled' => true
            ]
        ];

        $properties = [
            'Contact' => [
                [
                    'entityName' => 'Contact',
                    'propertyName' => 'name',
                    'propertyLabel' => 'Name',
                    'propertyType' => 'string',
                    'nullable' => false,
                    'length' => null,  // String requires length
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
                    'formType' => null,
                    'formOptions' => [],
                    'formRequired' => true,
                    'formReadOnly' => false,
                    'formHelp' => null,
                    'showInList' => true,
                    'showInDetail' => true,
                    'showInForm' => true,
                    'sortable' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'apiReadable' => true,
                    'apiWritable' => true,
                    'apiGroups' => [],
                    'translationKey' => null,
                    'formatPattern' => null,
                    'fixtureType' => null,
                    'fixtureOptions' => []
                ]
            ]
        ];

        $result = $this->validator->validateAll($entities, $properties);

        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('requires length', $result['errors'][0]);
    }
}
