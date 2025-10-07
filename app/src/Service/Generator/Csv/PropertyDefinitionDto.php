<?php

declare(strict_types=1);

namespace App\Service\Generator\Csv;

class PropertyDefinitionDto
{
    /**
     * @param array<string> $validationRules
     * @param array<string, mixed> $formOptions
     * @param array<string> $cascade
     * @param array<string, string> $orderBy
     * @param array<string> $apiGroups
     * @param array<string, mixed> $fixtureOptions
     */
    public function __construct(
        public readonly string $entityName,
        public readonly string $propertyName,
        public readonly string $propertyLabel,
        public readonly string $propertyType,
        public readonly bool $nullable,
        public readonly ?int $length,
        public readonly ?int $precision,
        public readonly ?int $scale,
        public readonly bool $unique,
        public readonly ?string $defaultValue,
        public readonly ?string $relationshipType,
        public readonly ?string $targetEntity,
        public readonly ?string $inversedBy,
        public readonly ?string $mappedBy,
        public readonly array $cascade,
        public readonly bool $orphanRemoval,
        public readonly ?string $fetch,
        public readonly array $orderBy,
        public readonly array $validationRules,
        public readonly ?string $validationMessage,
        public readonly ?string $formType,
        public readonly array $formOptions,
        public readonly bool $formRequired,
        public readonly bool $formReadOnly,
        public readonly ?string $formHelp,
        public readonly bool $showInList,
        public readonly bool $showInDetail,
        public readonly bool $showInForm,
        public readonly bool $sortable,
        public readonly bool $searchable,
        public readonly bool $filterable,
        public readonly bool $apiReadable,
        public readonly bool $apiWritable,
        public readonly array $apiGroups,
        public readonly ?string $translationKey,
        public readonly ?string $formatPattern,
        public readonly ?string $fixtureType,
        public readonly array $fixtureOptions
    ) {}

    /**
     * Create DTO from parsed CSV array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            entityName: $data['entityName'],
            propertyName: $data['propertyName'],
            propertyLabel: $data['propertyLabel'],
            propertyType: $data['propertyType'],
            nullable: $data['nullable'],
            length: $data['length'],
            precision: $data['precision'],
            scale: $data['scale'],
            unique: $data['unique'],
            defaultValue: $data['defaultValue'] ?? null,
            relationshipType: $data['relationshipType'] ?? null,
            targetEntity: $data['targetEntity'] ?? null,
            inversedBy: $data['inversedBy'] ?? null,
            mappedBy: $data['mappedBy'] ?? null,
            cascade: $data['cascade'],
            orphanRemoval: $data['orphanRemoval'],
            fetch: $data['fetch'] ?? null,
            orderBy: $data['orderBy'],
            validationRules: $data['validationRules'],
            validationMessage: $data['validationMessage'] ?? null,
            formType: $data['formType'] ?? null,
            formRequired: $data['formRequired'],
            formReadOnly: $data['formReadOnly'],
            formHelp: $data['formHelp'] ?? null,
            formOptions: $data['formOptions'],
            showInList: $data['showInList'],
            showInDetail: $data['showInDetail'],
            showInForm: $data['showInForm'],
            sortable: $data['sortable'],
            searchable: $data['searchable'],
            filterable: $data['filterable'],
            apiReadable: $data['apiReadable'],
            apiWritable: $data['apiWritable'],
            apiGroups: $data['apiGroups'],
            translationKey: $data['translationKey'] ?? null,
            formatPattern: $data['formatPattern'] ?? null,
            fixtureType: $data['fixtureType'] ?? null,
            fixtureOptions: $data['fixtureOptions']
        );
    }

    /**
     * Check if property is a relationship
     */
    public function isRelationship(): bool
    {
        return $this->relationshipType !== null;
    }

    /**
     * Check if property is collection (OneToMany, ManyToMany)
     */
    public function isCollection(): bool
    {
        return in_array($this->relationshipType, ['OneToMany', 'ManyToMany'], true);
    }

    /**
     * Check if property is single relationship (ManyToOne, OneToOne)
     */
    public function isSingleRelationship(): bool
    {
        return in_array($this->relationshipType, ['ManyToOne', 'OneToOne'], true);
    }

    /**
     * Get PHP type hint for property
     */
    public function getPhpType(): string
    {
        if ($this->isRelationship()) {
            if ($this->isCollection()) {
                return 'Collection';
            }
            return $this->targetEntity;
        }

        return match($this->propertyType) {
            'string', 'text' => 'string',
            'integer', 'smallint', 'bigint' => 'int',
            'decimal', 'float' => 'float',
            'boolean' => 'bool',
            'date', 'datetime', 'datetime_immutable', 'date_immutable',
            'time', 'datetimetz', 'datetimetz_immutable' => '\\DateTimeInterface',
            'array', 'simple_array', 'json' => 'array',
            default => 'mixed'
        };
    }

    /**
     * Get default Symfony form type if not specified
     */
    public function getFormType(): string
    {
        if ($this->formType !== null) {
            return $this->formType;
        }

        if ($this->isRelationship()) {
            return 'EntityType';
        }

        return match($this->propertyType) {
            'text' => 'TextareaType',
            'integer', 'smallint', 'bigint' => 'IntegerType',
            'decimal', 'float' => 'NumberType',
            'boolean' => 'CheckboxType',
            'date' => 'DateType',
            'datetime', 'datetime_immutable' => 'DateTimeType',
            'time' => 'TimeType',
            default => 'TextType'
        };
    }
}
