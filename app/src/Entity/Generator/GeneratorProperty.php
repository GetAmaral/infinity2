<?php

declare(strict_types=1);

namespace App\Entity\Generator;

use App\Doctrine\UuidV7Generator;
use App\Repository\Generator\GeneratorPropertyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ORM\Entity(repositoryClass: GeneratorPropertyRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    security: "is_granted('ROLE_ADMIN')",
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]
class GeneratorProperty
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private Uuid $id;

    // ====================================
    // PARENT ENTITY
    // ====================================

    #[ORM\ManyToOne(targetEntity: GeneratorEntity::class, inversedBy: 'properties')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GeneratorEntity $entity;

    // ====================================
    // BASIC INFORMATION
    // ====================================

    #[ORM\Column(length: 100)]
    private string $propertyName;         // "emailAddress" (camelCase)

    #[ORM\Column(length: 100)]
    private string $propertyLabel;        // "Email Address"

    #[ORM\Column(length: 50)]
    private string $propertyType;         // "string", "integer", "datetime"

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $propertyOrder = 0;       // Display order

    // ====================================
    // DATABASE CONFIGURATION
    // ====================================

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $nullable = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $length = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $precision = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $scale = null;

    #[ORM\Column(name: 'unique_prop', type: 'boolean', options: ['default' => false])]
    private bool $unique = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private mixed $defaultValue = null;

    // Database Indexing
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $indexed = false;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $indexType = null;       // 'INDEX', 'UNIQUE', 'FULLTEXT', 'SPATIAL'

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $compositeIndexWith = null;  // ['property1', 'property2'] for composite indexes

    // ====================================
    // ENUM SUPPORT (PHP 8.1+)
    // ====================================

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isEnum = false;            // Property is a PHP enum

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $enumClass = null;       // Full enum class name, e.g., 'App\Enum\StatusEnum'

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $enumValues = null;       // Fallback enum values if not using PHP enum class

    // ====================================
    // COMPUTED/VIRTUAL PROPERTIES (PHP 8.4+)
    // ====================================

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isVirtual = false;         // Not persisted to database, computed on-the-fly

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $computeExpression = null;  // Expression for computing value, e.g., "firstName + ' ' + lastName"

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $usePropertyHook = false;   // Use PHP 8.4 property hooks for get/set logic

    // ====================================
    // POSTGRESQL-SPECIFIC FEATURES
    // ====================================

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isJsonb = false;        // Use JSONB instead of JSON (PostgreSQL)

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $useFullTextSearch = false;  // Enable trgm/ts_vector full-text search

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isArrayType = false;    // Use PostgreSQL native array type

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $pgArrayType = null;  // 'text[]', 'integer[]', 'uuid[]'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $checkConstraint = null;  // SQL CHECK constraint, e.g., "value > 0 AND value < 100"

    // ====================================
    // RELATIONSHIPS
    // ====================================

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $relationshipType = null; // 'ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne'

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $targetEntity = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $inversedBy = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mappedBy = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $cascade = null;       // ['persist', 'remove']

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $orphanRemoval = false;

    #[ORM\Column(name: 'fetch_prop', length: 20, nullable: true, options: ['default' => 'LAZY'])]
    private ?string $fetch = 'LAZY';      // 'LAZY', 'EAGER', 'EXTRA_LAZY'

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $orderBy = null;       // {"name": "ASC"}

    // ====================================
    // EMBEDDED OBJECTS (Value Objects / Embeddables)
    // ====================================

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isEmbedded = false;     // Property is an embedded value object

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $embeddedClass = null;  // Embedded class name, e.g., 'App\ValueObject\Address'

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $embeddedPrefix = null;  // Column prefix for embedded properties, e.g., 'billing_', 'shipping_'

    // ====================================
    // VALIDATION
    // ====================================

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $validationRules = null;  // ['NotBlank', 'Email', 'Length' => ['min' => 5]]

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $validationMessage = null;

    // Enhanced Validation
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $validationGroups = null;  // ['create', 'update', 'admin'] - context-aware validation

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customValidator = null;  // Custom validator class, e.g., 'App\Validator\CustomValidator'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $validationCondition = null;  // Conditional validation, e.g., "entity.status == 'active'"

    // ====================================
    // FORM CONFIGURATION
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $formType = null;     // "EmailType", "TextType", "EntityType"

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $formOptions = null;   // {"attr": {"placeholder": "Enter email"}}

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $formRequired = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $formReadOnly = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $formHelp = null;

    // ====================================
    // UI DISPLAY
    // ====================================

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $showInList = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $showInDetail = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $showInForm = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $sortable = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $searchable = false;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $filterable = false;

    // ====================================
    // API CONFIGURATION
    // ====================================

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $apiReadable = true;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $apiWritable = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiGroups = null;     // ["contact:read", "contact:write"]

    // API Platform Advanced Features
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isSubresource = false;  // Expose as API subresource

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subresourcePath = null;  // e.g., '/contacts/{id}/addresses'

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $exposeIri = false;      // Expose as IRI instead of embedded object

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $apiDescription = null;  // OpenAPI field description

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $apiExample = null;   // Example value for API documentation

    // Advanced API Platform Filters
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $filterStrategy = null;  // 'partial', 'exact', 'start', 'end', 'word_start'

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $filterSearchable = false;  // Enable full-text search filter

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $filterOrderable = false;  // Enable ordering on this property

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $filterBoolean = false;  // Enable boolean filter

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $filterDate = false;  // Enable date range filter

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $filterNumericRange = false;  // Enable numeric range filter

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $filterExists = false;  // Enable null/not-null filter

    // ====================================
    // FIELD-LEVEL SECURITY
    // ====================================

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $allowedRoles = null;  // ['ROLE_ADMIN', 'ROLE_USER'] - roles that can view/edit this field

    // ====================================
    // LOCALIZATION
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $translationKey = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $formatPattern = null;

    // ====================================
    // SERIALIZATION CONTROL
    // ====================================

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $serializerContext = null;  // Additional Symfony Serializer context options

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $serializerMethod = null;  // Custom accessor method for serialization, e.g., 'getName'

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $denormalizer = null;      // Custom denormalizer class for complex type conversions

    // ====================================
    // FIXTURES
    // ====================================

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $fixtureType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $fixtureOptions = null;

    // ====================================
    // AUDIT
    // ====================================

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters (all 38 fields)
    public function getId(): Uuid { return $this->id; }
    public function getEntity(): GeneratorEntity { return $this->entity; }
    public function setEntity(GeneratorEntity $entity): self { $this->entity = $entity; return $this; }
    public function getPropertyName(): string { return $this->propertyName; }
    public function setPropertyName(string $propertyName): self { $this->propertyName = $propertyName; return $this; }
    public function getPropertyLabel(): string { return $this->propertyLabel; }
    public function setPropertyLabel(string $propertyLabel): self { $this->propertyLabel = $propertyLabel; return $this; }
    public function getPropertyType(): string { return $this->propertyType; }
    public function setPropertyType(string $propertyType): self { $this->propertyType = $propertyType; return $this; }
    public function getPropertyOrder(): int { return $this->propertyOrder; }
    public function setPropertyOrder(int $propertyOrder): self { $this->propertyOrder = $propertyOrder; return $this; }
    public function isNullable(): bool { return $this->nullable; }
    public function setNullable(bool $nullable): self { $this->nullable = $nullable; return $this; }
    public function getLength(): ?int { return $this->length; }
    public function setLength(?int $length): self { $this->length = $length; return $this; }
    public function getPrecision(): ?int { return $this->precision; }
    public function setPrecision(?int $precision): self { $this->precision = $precision; return $this; }
    public function getScale(): ?int { return $this->scale; }
    public function setScale(?int $scale): self { $this->scale = $scale; return $this; }
    public function isUnique(): bool { return $this->unique; }
    public function setUnique(bool $unique): self { $this->unique = $unique; return $this; }
    public function getDefaultValue(): mixed { return $this->defaultValue; }
    public function setDefaultValue(mixed $defaultValue): self { $this->defaultValue = $defaultValue; return $this; }
    public function getRelationshipType(): ?string { return $this->relationshipType; }
    public function setRelationshipType(?string $relationshipType): self { $this->relationshipType = $relationshipType; return $this; }
    public function getTargetEntity(): ?string { return $this->targetEntity; }
    public function setTargetEntity(?string $targetEntity): self { $this->targetEntity = $targetEntity; return $this; }
    public function getInversedBy(): ?string { return $this->inversedBy; }
    public function setInversedBy(?string $inversedBy): self { $this->inversedBy = $inversedBy; return $this; }
    public function getMappedBy(): ?string { return $this->mappedBy; }
    public function setMappedBy(?string $mappedBy): self { $this->mappedBy = $mappedBy; return $this; }
    public function getCascade(): ?array { return $this->cascade; }
    public function setCascade(?array $cascade): self { $this->cascade = $cascade; return $this; }
    public function isOrphanRemoval(): bool { return $this->orphanRemoval; }
    public function setOrphanRemoval(bool $orphanRemoval): self { $this->orphanRemoval = $orphanRemoval; return $this; }
    public function getFetch(): ?string { return $this->fetch; }
    public function setFetch(?string $fetch): self { $this->fetch = $fetch; return $this; }
    public function getOrderBy(): ?array { return $this->orderBy; }
    public function setOrderBy(?array $orderBy): self { $this->orderBy = $orderBy; return $this; }
    public function getValidationRules(): ?array { return $this->validationRules; }
    public function setValidationRules(?array $validationRules): self { $this->validationRules = $validationRules; return $this; }
    public function getValidationMessage(): ?string { return $this->validationMessage; }
    public function setValidationMessage(?string $validationMessage): self { $this->validationMessage = $validationMessage; return $this; }
    public function getFormType(): ?string { return $this->formType; }
    public function setFormType(?string $formType): self { $this->formType = $formType; return $this; }
    public function getFormOptions(): ?array { return $this->formOptions; }
    public function setFormOptions(?array $formOptions): self { $this->formOptions = $formOptions; return $this; }
    public function isFormRequired(): bool { return $this->formRequired; }
    public function setFormRequired(bool $formRequired): self { $this->formRequired = $formRequired; return $this; }
    public function isFormReadOnly(): bool { return $this->formReadOnly; }
    public function setFormReadOnly(bool $formReadOnly): self { $this->formReadOnly = $formReadOnly; return $this; }
    public function getFormHelp(): ?string { return $this->formHelp; }
    public function setFormHelp(?string $formHelp): self { $this->formHelp = $formHelp; return $this; }
    public function isShowInList(): bool { return $this->showInList; }
    public function setShowInList(bool $showInList): self { $this->showInList = $showInList; return $this; }
    public function isShowInDetail(): bool { return $this->showInDetail; }
    public function setShowInDetail(bool $showInDetail): self { $this->showInDetail = $showInDetail; return $this; }
    public function isShowInForm(): bool { return $this->showInForm; }
    public function setShowInForm(bool $showInForm): self { $this->showInForm = $showInForm; return $this; }
    public function isSortable(): bool { return $this->sortable; }
    public function setSortable(bool $sortable): self { $this->sortable = $sortable; return $this; }
    public function isSearchable(): bool { return $this->searchable; }
    public function setSearchable(bool $searchable): self { $this->searchable = $searchable; return $this; }
    public function isFilterable(): bool { return $this->filterable; }
    public function setFilterable(bool $filterable): self { $this->filterable = $filterable; return $this; }
    public function isApiReadable(): bool { return $this->apiReadable; }
    public function setApiReadable(bool $apiReadable): self { $this->apiReadable = $apiReadable; return $this; }
    public function isApiWritable(): bool { return $this->apiWritable; }
    public function setApiWritable(bool $apiWritable): self { $this->apiWritable = $apiWritable; return $this; }
    public function getApiGroups(): ?array { return $this->apiGroups; }
    public function setApiGroups(?array $apiGroups): self { $this->apiGroups = $apiGroups; return $this; }
    public function getTranslationKey(): ?string { return $this->translationKey; }
    public function setTranslationKey(?string $translationKey): self { $this->translationKey = $translationKey; return $this; }
    public function getFormatPattern(): ?string { return $this->formatPattern; }
    public function setFormatPattern(?string $formatPattern): self { $this->formatPattern = $formatPattern; return $this; }
    public function getFixtureType(): ?string { return $this->fixtureType; }
    public function setFixtureType(?string $fixtureType): self { $this->fixtureType = $fixtureType; return $this; }
    public function getFixtureOptions(): ?array { return $this->fixtureOptions; }
    public function setFixtureOptions(?array $fixtureOptions): self { $this->fixtureOptions = $fixtureOptions; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // New getters and setters for indexed, indexType, compositeIndexWith, allowedRoles
    public function isIndexed(): bool { return $this->indexed; }
    public function setIndexed(bool $indexed): self { $this->indexed = $indexed; return $this; }
    public function getIndexType(): ?string { return $this->indexType; }
    public function setIndexType(?string $indexType): self { $this->indexType = $indexType; return $this; }
    public function getCompositeIndexWith(): ?array { return $this->compositeIndexWith; }
    public function setCompositeIndexWith(?array $compositeIndexWith): self { $this->compositeIndexWith = $compositeIndexWith; return $this; }
    public function getAllowedRoles(): ?array { return $this->allowedRoles; }
    public function setAllowedRoles(?array $allowedRoles): self { $this->allowedRoles = $allowedRoles; return $this; }

    // API Platform advanced features getters and setters
    public function isSubresource(): bool { return $this->isSubresource; }
    public function setIsSubresource(bool $isSubresource): self { $this->isSubresource = $isSubresource; return $this; }
    public function getSubresourcePath(): ?string { return $this->subresourcePath; }
    public function setSubresourcePath(?string $subresourcePath): self { $this->subresourcePath = $subresourcePath; return $this; }
    public function isExposeIri(): bool { return $this->exposeIri; }
    public function setExposeIri(bool $exposeIri): self { $this->exposeIri = $exposeIri; return $this; }
    public function getApiDescription(): ?string { return $this->apiDescription; }
    public function setApiDescription(?string $apiDescription): self { $this->apiDescription = $apiDescription; return $this; }
    public function getApiExample(): ?string { return $this->apiExample; }
    public function setApiExample(?string $apiExample): self { $this->apiExample = $apiExample; return $this; }

    // PostgreSQL-specific features getters and setters
    public function isJsonb(): bool { return $this->isJsonb; }
    public function setIsJsonb(bool $isJsonb): self { $this->isJsonb = $isJsonb; return $this; }
    public function isUseFullTextSearch(): bool { return $this->useFullTextSearch; }
    public function setUseFullTextSearch(bool $useFullTextSearch): self { $this->useFullTextSearch = $useFullTextSearch; return $this; }
    public function isArrayType(): bool { return $this->isArrayType; }
    public function setIsArrayType(bool $isArrayType): self { $this->isArrayType = $isArrayType; return $this; }
    public function getPgArrayType(): ?string { return $this->pgArrayType; }
    public function setPgArrayType(?string $pgArrayType): self { $this->pgArrayType = $pgArrayType; return $this; }
    public function getCheckConstraint(): ?string { return $this->checkConstraint; }
    public function setCheckConstraint(?string $checkConstraint): self { $this->checkConstraint = $checkConstraint; return $this; }

    // Enum support getters and setters
    public function isEnum(): bool { return $this->isEnum; }
    public function setIsEnum(bool $isEnum): self { $this->isEnum = $isEnum; return $this; }
    public function getEnumClass(): ?string { return $this->enumClass; }
    public function setEnumClass(?string $enumClass): self { $this->enumClass = $enumClass; return $this; }
    public function getEnumValues(): ?array { return $this->enumValues; }
    public function setEnumValues(?array $enumValues): self { $this->enumValues = $enumValues; return $this; }

    // Computed/virtual properties getters and setters
    public function isVirtual(): bool { return $this->isVirtual; }
    public function setIsVirtual(bool $isVirtual): self { $this->isVirtual = $isVirtual; return $this; }
    public function getComputeExpression(): ?string { return $this->computeExpression; }
    public function setComputeExpression(?string $computeExpression): self { $this->computeExpression = $computeExpression; return $this; }
    public function isUsePropertyHook(): bool { return $this->usePropertyHook; }
    public function setUsePropertyHook(bool $usePropertyHook): self { $this->usePropertyHook = $usePropertyHook; return $this; }

    // Enhanced validation getters and setters
    public function getValidationGroups(): ?array { return $this->validationGroups; }
    public function setValidationGroups(?array $validationGroups): self { $this->validationGroups = $validationGroups; return $this; }
    public function getCustomValidator(): ?string { return $this->customValidator; }
    public function setCustomValidator(?string $customValidator): self { $this->customValidator = $customValidator; return $this; }
    public function getValidationCondition(): ?string { return $this->validationCondition; }
    public function setValidationCondition(?string $validationCondition): self { $this->validationCondition = $validationCondition; return $this; }

    // Embedded objects getters and setters
    public function isEmbedded(): bool { return $this->isEmbedded; }
    public function setIsEmbedded(bool $isEmbedded): self { $this->isEmbedded = $isEmbedded; return $this; }
    public function getEmbeddedClass(): ?string { return $this->embeddedClass; }
    public function setEmbeddedClass(?string $embeddedClass): self { $this->embeddedClass = $embeddedClass; return $this; }
    public function getEmbeddedPrefix(): ?string { return $this->embeddedPrefix; }
    public function setEmbeddedPrefix(?string $embeddedPrefix): self { $this->embeddedPrefix = $embeddedPrefix; return $this; }

    // Serialization control getters and setters
    public function getSerializerContext(): ?array { return $this->serializerContext; }
    public function setSerializerContext(?array $serializerContext): self { $this->serializerContext = $serializerContext; return $this; }
    public function getSerializerMethod(): ?string { return $this->serializerMethod; }
    public function setSerializerMethod(?string $serializerMethod): self { $this->serializerMethod = $serializerMethod; return $this; }
    public function getDenormalizer(): ?string { return $this->denormalizer; }
    public function setDenormalizer(?string $denormalizer): self { $this->denormalizer = $denormalizer; return $this; }

    // Advanced filters getters and setters
    public function getFilterStrategy(): ?string { return $this->filterStrategy; }
    public function setFilterStrategy(?string $filterStrategy): self { $this->filterStrategy = $filterStrategy; return $this; }
    public function isFilterSearchable(): bool { return $this->filterSearchable; }
    public function setFilterSearchable(bool $filterSearchable): self { $this->filterSearchable = $filterSearchable; return $this; }
    public function isFilterOrderable(): bool { return $this->filterOrderable; }
    public function setFilterOrderable(bool $filterOrderable): self { $this->filterOrderable = $filterOrderable; return $this; }
    public function isFilterBoolean(): bool { return $this->filterBoolean; }
    public function setFilterBoolean(bool $filterBoolean): self { $this->filterBoolean = $filterBoolean; return $this; }
    public function isFilterDate(): bool { return $this->filterDate; }
    public function setFilterDate(bool $filterDate): self { $this->filterDate = $filterDate; return $this; }
    public function isFilterNumericRange(): bool { return $this->filterNumericRange; }
    public function setFilterNumericRange(bool $filterNumericRange): self { $this->filterNumericRange = $filterNumericRange; return $this; }
    public function isFilterExists(): bool { return $this->filterExists; }
    public function setFilterExists(bool $filterExists): self { $this->filterExists = $filterExists; return $this; }
}
