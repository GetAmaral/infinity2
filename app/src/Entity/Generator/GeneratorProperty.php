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
    // BASIC INFORMATION (4 fields)
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
    // DATABASE CONFIGURATION (6 fields)
    // ====================================

    #[ORM\Column(options: ['default' => false])]
    private bool $nullable = false;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $length = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $precision = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $scale = null;

    #[ORM\Column(name: 'is_unique', options: ['default' => false])]
    private bool $unique = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $defaultValue = null;

    // ====================================
    // RELATIONSHIPS (8 fields)
    // ====================================

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $relationshipType = null; // 'ManyToOne', 'OneToMany', 'ManyToMany', 'OneToOne'

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $targetEntity = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $inversedBy = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $mappedBy = null;

    #[ORM\Column(name: 'cascade_actions', type: 'json', nullable: true)]
    private ?array $cascade = null;       // ['persist', 'remove']

    #[ORM\Column(options: ['default' => false])]
    private bool $orphanRemoval = false;

    #[ORM\Column(name: 'fetch_type', length: 20, nullable: true, options: ['default' => 'LAZY'])]
    private ?string $fetch = 'LAZY';      // 'LAZY', 'EAGER', 'EXTRA_LAZY'

    #[ORM\Column(name: 'order_by_fields', type: 'json', nullable: true)]
    private ?array $orderBy = null;       // {"name": "ASC"}

    // ====================================
    // VALIDATION (2 fields)
    // ====================================

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $validationRules = null;  // ['NotBlank', 'Email', 'Length' => ['min' => 5]]

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $validationMessage = null;

    // ====================================
    // FORM CONFIGURATION (5 fields)
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $formType = null;     // "EmailType", "TextType", "EntityType"

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $formOptions = null;   // {"attr": {"placeholder": "Enter email"}}

    #[ORM\Column(options: ['default' => false])]
    private bool $formRequired = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $formReadOnly = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $formHelp = null;

    // ====================================
    // UI DISPLAY (6 fields)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $showInList = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showInDetail = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $showInForm = true;

    #[ORM\Column(options: ['default' => false])]
    private bool $sortable = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $searchable = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $filterable = false;

    // ====================================
    // API CONFIGURATION (3 fields)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $apiReadable = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $apiWritable = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiGroups = null;     // ["contact:read", "contact:write"]

    // ====================================
    // LOCALIZATION (2 fields)
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $translationKey = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $formatPattern = null;

    // ====================================
    // FIXTURES (2 fields)
    // ====================================

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $fixtureType = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $fixtureOptions = null;

    // ====================================
    // AUDIT (2 fields)
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
    public function getDefaultValue(): ?string { return $this->defaultValue; }
    public function setDefaultValue(?string $defaultValue): self { $this->defaultValue = $defaultValue; return $this; }
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
}
