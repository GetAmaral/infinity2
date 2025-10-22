<?php

declare(strict_types=1);

namespace App\Entity\Generator;

use App\Doctrine\UuidV7Generator;
use App\Repository\Generator\GeneratorEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ORM\Entity(repositoryClass: GeneratorEntityRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_entity_name', columns: ['entity_name'])]
#[ORM\Index(name: 'idx_menu_group_order', columns: ['menu_group', 'menu_order'])]
#[ORM\Index(name: 'idx_generated_status', columns: ['is_generated', 'last_generated_at'])]
#[ApiResource(
    security: "is_granted('ROLE_ADMIN')",
    operations: [
        new GetCollection(),
        new Get(),
        new Post(),
        new Put(),
        new Delete()
    ],
    normalizationContext: ['groups' => ['generator_entity:read']],
    denormalizationContext: ['groups' => ['generator_entity:write']]
)]
class GeneratorEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    #[Groups(['generator_entity:read'])]
    private Uuid $id;

    // ====================================
    // BASIC INFORMATION (5 fields)
    // ====================================

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Entity name is required')]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: '/^[A-Z][a-zA-Z0-9]*$/',
        message: 'Entity name must be in PascalCase (e.g., Contact, UserProfile)'
    )]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private string $entityName;           // "Contact" (PascalCase)

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Entity label is required')]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private string $entityLabel;          // "Contact"

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Plural label is required')]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private string $pluralLabel;          // "Contacts"

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Icon is required')]
    #[Assert\Regex(
        pattern: '/^bi-[a-z0-9-]+$/',
        message: 'Icon must be a valid Bootstrap icon (e.g., bi-person)'
    )]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private string $icon;                 // "bi-person"

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?string $description = null;

    // ====================================
    // CANVAS POSITIONING (2 fields)
    // ====================================

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    #[Assert\Range(min: 0, max: 10000)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private int $canvasX = 100;

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    #[Assert\Range(min: 0, max: 10000)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private int $canvasY = 100;

    // ====================================
    // MULTI-TENANCY (1 field)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $hasOrganization = true;

    // ====================================
    // API CONFIGURATION (8 fields)
    // ====================================

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $apiEnabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $apiOperations = null;    // ['GetCollection', 'Get', 'Post', 'Put', 'Delete']

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?string $apiSecurity = null;     // "is_granted('ROLE_USER')"

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $apiNormalizationContext = null;  // ['groups' => ['entity:read']]

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $apiDenormalizationContext = null;  // ['groups' => ['entity:write']]

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $apiDefaultOrder = null;  // {"name": "asc"}

    // Operation-Level Configuration
    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $operationSecurity = null;  // Per-operation security: ['GetCollection' => "is_granted('ROLE_USER')", 'Post' => "is_granted('ROLE_ADMIN')"]

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $operationValidationGroups = null;  // Per-operation validation: ['Post' => ['create', 'strict'], 'Put' => ['update']]

    // ====================================
    // SECURITY (2 fields)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $voterEnabled = true;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $voterAttributes = null;  // ['VIEW', 'EDIT', 'DELETE', 'CREATE']

    // ====================================
    // VALIDATION (2 fields)
    // ====================================

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $validationGroups = null;  // ['create', 'update', 'admin'] - context-aware validation

    // ====================================
    // INPUT DTO CONFIGURATION (9 fields)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $dtoEnabled = true;  // Enable DTO generation

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $dtoIncludeNestedCreate = true;  // Allow creating nested objects

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $dtoIncludeNestedUpdate = true;  // Allow updating nested objects via @id

    #[ORM\Column(type: 'integer', options: ['default' => 2])]
    #[Assert\Range(min: 1, max: 5)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private int $dtoMaxNestingDepth = 2;  // Max levels of nesting (prevent circular refs)

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $dtoExcludedProperties = null;  // Properties to exclude from DTO: ['id', 'createdAt']

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $crossFieldValidationRules = null;  // [{"rule": "startDate < endDate", "message": "..."}]

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $uniqueConstraints = null;  // [{"fields": ["userId", "courseId"], "message": "..."}]

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $businessRules = null;  // [{"type": "expression", "rule": "...", "message": "..."}]

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $validationMessages = null;  // Default messages for common validations

    // ====================================
    // NAVIGATION (2 fields)
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?string $menuGroup = null;       // "CRM", "System", "Reports"

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    #[Assert\Range(min: 0, max: 9999)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private int $menuOrder = 100;

    // ====================================
    // TESTING (1 field)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $testEnabled = true;

    // ====================================
    // ADDITIONAL CONFIGURATION (6 fields)
    // ====================================

    #[ORM\Column(length: 255, options: ['default' => 'App\\Entity'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private string $namespace = 'App\\Entity';

    #[ORM\Column(options: ['default' => true])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $fixturesEnabled = true;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private bool $auditEnabled = false;

    #[ORM\Column(length: 7, options: ['default' => '#6c757d'])]
    #[Assert\Regex(
        pattern: '/^#[0-9A-Fa-f]{6}$/',
        message: 'Color must be a valid hex color (e.g., #6c757d)'
    )]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private string $color = '#6c757d';

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['generator_entity:read', 'generator_entity:write'])]
    private ?array $tags = null;  // ['crm', 'sales', 'customer']

    // ====================================
    // RELATIONSHIPS
    // ====================================

    #[ORM\OneToMany(
        mappedBy: 'entity',
        targetEntity: GeneratorProperty::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['propertyOrder' => 'ASC'])]
    #[Groups(['generator_entity:read'])]
    private Collection $properties;

    // ====================================
    // GENERATION STATUS (3 fields)
    // ====================================

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['generator_entity:read'])]
    private bool $isGenerated = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['generator_entity:read'])]
    private ?\DateTimeImmutable $lastGeneratedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['generator_entity:read'])]
    private ?string $lastGenerationLog = null;

    // ====================================
    // AUDIT (2 fields)
    // ====================================

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['generator_entity:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['generator_entity:read'])]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ====================================
    // STRING REPRESENTATION
    // ====================================

    public function __toString(): string
    {
        return $this->entityLabel;
    }

    // ====================================
    // DOMAIN LOGIC METHODS
    // ====================================

    /**
     * Check if the entity can be deleted
     */
    public function canBeDeleted(): bool
    {
        return !$this->isGenerated;
    }

    /**
     * Check if the entity can be generated
     */
    public function canBeGenerated(): bool
    {
        return !$this->isGenerated && $this->properties->count() > 0;
    }

    /**
     * Mark entity as successfully generated
     */
    public function markAsGenerated(string $log): self
    {
        $this->isGenerated = true;
        $this->lastGeneratedAt = new \DateTimeImmutable();
        $this->lastGenerationLog = $log;
        return $this;
    }

    /**
     * Mark entity as failed generation
     */
    public function markAsFailed(string $errorLog): self
    {
        $this->isGenerated = false;
        $this->lastGenerationLog = $errorLog;
        return $this;
    }

    /**
     * Reset generation status
     */
    public function resetGeneration(): self
    {
        $this->isGenerated = false;
        $this->lastGeneratedAt = null;
        $this->lastGenerationLog = null;
        return $this;
    }

    /**
     * Get entity slug (snake_case from PascalCase)
     */
    public function getSlug(): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->entityName));
    }

    /**
     * Get database table name
     *
     * Uses Utils::camelToSnakeCase() to convert entity name.
     * ONLY adds _table suffix if the snake_case name is a reserved SQL keyword.
     * This matches the logic in ReservedKeywordExtension for consistency.
     *
     * Note: For reserved keyword checking, use ReservedKeywordExtension::isReservedKeyword()
     * This method provides a simplified version for basic usage.
     */
    public function getTableName(): string
    {
        // Reuse Utils function instead of reinventing wheel
        return \App\Service\Utils::camelToSnakeCase($this->entityName, false);

        // Note: Suffix logic is in ReservedKeywordExtension for template generation
        // This simplified version returns just snake_case for database queries
    }

    /**
     * Get fully qualified class name
     */
    public function getFullyQualifiedClassName(): string
    {
        return rtrim($this->namespace, '\\') . '\\' . $this->entityName;
    }

    /**
     * Get repository class name
     */
    public function getRepositoryClassName(): string
    {
        return str_replace('\\Entity\\', '\\Repository\\', $this->namespace)
            . '\\' . $this->entityName . 'Repository';
    }

    /**
     * Check if entity has a specific tag
     */
    public function hasTag(string $tag): bool
    {
        return $this->tags !== null && in_array($tag, $this->tags, true);
    }

    /**
     * Add a tag
     */
    public function addTag(string $tag): self
    {
        if ($this->tags === null) {
            $this->tags = [];
        }

        if (!in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    /**
     * Remove a tag
     */
    public function removeTag(string $tag): self
    {
        if ($this->tags !== null) {
            $this->tags = array_values(array_filter($this->tags, fn($t) => $t !== $tag));
        }

        return $this;
    }

    /**
     * Get configuration hash
     */
    public function getConfigurationHash(): string
    {
        return md5(serialize([
            $this->entityName,
            $this->hasOrganization,
            $this->apiEnabled,
            $this->apiOperations,
            $this->voterEnabled,
            $this->voterAttributes,
            $this->namespace,
            // tableName removed - now calculated from entityName
        ]));
    }

    // ====================================
    // GETTERS AND SETTERS
    // ====================================

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): self
    {
        $this->entityName = $entityName;
        return $this;
    }

    public function getEntityLabel(): string
    {
        return $this->entityLabel;
    }

    public function setEntityLabel(string $entityLabel): self
    {
        $this->entityLabel = $entityLabel;
        return $this;
    }

    public function getPluralLabel(): string
    {
        return $this->pluralLabel;
    }

    public function setPluralLabel(string $pluralLabel): self
    {
        $this->pluralLabel = $pluralLabel;
        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCanvasX(): int
    {
        return $this->canvasX;
    }

    public function setCanvasX(int $canvasX): self
    {
        $this->canvasX = $canvasX;
        return $this;
    }

    public function getCanvasY(): int
    {
        return $this->canvasY;
    }

    public function setCanvasY(int $canvasY): self
    {
        $this->canvasY = $canvasY;
        return $this;
    }

    public function isHasOrganization(): bool
    {
        return $this->hasOrganization;
    }

    public function setHasOrganization(bool $hasOrganization): self
    {
        $this->hasOrganization = $hasOrganization;
        return $this;
    }

    public function isApiEnabled(): bool
    {
        return $this->apiEnabled;
    }

    public function setApiEnabled(bool $apiEnabled): self
    {
        $this->apiEnabled = $apiEnabled;
        return $this;
    }

    public function getApiOperations(): ?array
    {
        return $this->apiOperations;
    }

    public function setApiOperations(?array $apiOperations): self
    {
        $this->apiOperations = $apiOperations;
        return $this;
    }

    public function getApiSecurity(): ?string
    {
        return $this->apiSecurity;
    }

    public function setApiSecurity(?string $apiSecurity): self
    {
        $this->apiSecurity = $apiSecurity;
        return $this;
    }

    public function getApiNormalizationContext(): ?array
    {
        return $this->apiNormalizationContext;
    }

    public function setApiNormalizationContext(?array $apiNormalizationContext): self
    {
        $this->apiNormalizationContext = $apiNormalizationContext;
        return $this;
    }

    public function getApiDenormalizationContext(): ?array
    {
        return $this->apiDenormalizationContext;
    }

    public function setApiDenormalizationContext(?array $apiDenormalizationContext): self
    {
        $this->apiDenormalizationContext = $apiDenormalizationContext;
        return $this;
    }

    public function getApiDefaultOrder(): ?array
    {
        return $this->apiDefaultOrder;
    }

    public function setApiDefaultOrder(?array $apiDefaultOrder): self
    {
        $this->apiDefaultOrder = $apiDefaultOrder;
        return $this;
    }

    public function isVoterEnabled(): bool
    {
        return $this->voterEnabled;
    }

    public function setVoterEnabled(bool $voterEnabled): self
    {
        $this->voterEnabled = $voterEnabled;
        return $this;
    }

    public function getVoterAttributes(): ?array
    {
        return $this->voterAttributes;
    }

    public function setVoterAttributes(?array $voterAttributes): self
    {
        $this->voterAttributes = $voterAttributes;
        return $this;
    }

    public function getMenuGroup(): ?string
    {
        return $this->menuGroup;
    }

    public function setMenuGroup(?string $menuGroup): self
    {
        $this->menuGroup = $menuGroup;
        return $this;
    }

    public function getMenuOrder(): int
    {
        return $this->menuOrder;
    }

    public function setMenuOrder(int $menuOrder): self
    {
        $this->menuOrder = $menuOrder;
        return $this;
    }

    public function isTestEnabled(): bool
    {
        return $this->testEnabled;
    }

    public function setTestEnabled(bool $testEnabled): self
    {
        $this->testEnabled = $testEnabled;
        return $this;
    }

    public function getNamespace(): string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function isFixturesEnabled(): bool
    {
        return $this->fixturesEnabled;
    }

    public function setFixturesEnabled(bool $fixturesEnabled): self
    {
        $this->fixturesEnabled = $fixturesEnabled;
        return $this;
    }

    public function isAuditEnabled(): bool
    {
        return $this->auditEnabled;
    }

    public function setAuditEnabled(bool $auditEnabled): self
    {
        $this->auditEnabled = $auditEnabled;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getProperties(): Collection
    {
        return $this->properties;
    }

    public function addProperty(GeneratorProperty $property): self
    {
        if (!$this->properties->contains($property)) {
            $this->properties->add($property);
            $property->setEntity($this);
        }
        return $this;
    }

    public function removeProperty(GeneratorProperty $property): self
    {
        if ($this->properties->removeElement($property)) {
            if ($property->getEntity() === $this) {
                $property->setEntity(null);
            }
        }
        return $this;
    }

    public function isGenerated(): bool
    {
        return $this->isGenerated;
    }

    public function setIsGenerated(bool $isGenerated): self
    {
        $this->isGenerated = $isGenerated;
        return $this;
    }

    public function getLastGeneratedAt(): ?\DateTimeImmutable
    {
        return $this->lastGeneratedAt;
    }

    public function setLastGeneratedAt(?\DateTimeImmutable $lastGeneratedAt): self
    {
        $this->lastGeneratedAt = $lastGeneratedAt;
        return $this;
    }

    public function getLastGenerationLog(): ?string
    {
        return $this->lastGenerationLog;
    }

    public function setLastGenerationLog(?string $lastGenerationLog): self
    {
        $this->lastGenerationLog = $lastGenerationLog;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getOperationSecurity(): ?array
    {
        return $this->operationSecurity;
    }

    public function setOperationSecurity(?array $operationSecurity): self
    {
        $this->operationSecurity = $operationSecurity;
        return $this;
    }

    public function getOperationValidationGroups(): ?array
    {
        return $this->operationValidationGroups;
    }

    public function setOperationValidationGroups(?array $operationValidationGroups): self
    {
        $this->operationValidationGroups = $operationValidationGroups;
        return $this;
    }

    public function getValidationGroups(): ?array
    {
        return $this->validationGroups;
    }

    public function setValidationGroups(?array $validationGroups): self
    {
        $this->validationGroups = $validationGroups;
        return $this;
    }

    // ====================================
    // DTO CONFIGURATION GETTERS/SETTERS
    // ====================================

    public function isDtoEnabled(): bool
    {
        return $this->dtoEnabled;
    }

    public function setDtoEnabled(bool $dtoEnabled): self
    {
        $this->dtoEnabled = $dtoEnabled;
        return $this;
    }

    public function isDtoIncludeNestedCreate(): bool
    {
        return $this->dtoIncludeNestedCreate;
    }

    public function setDtoIncludeNestedCreate(bool $dtoIncludeNestedCreate): self
    {
        $this->dtoIncludeNestedCreate = $dtoIncludeNestedCreate;
        return $this;
    }

    public function isDtoIncludeNestedUpdate(): bool
    {
        return $this->dtoIncludeNestedUpdate;
    }

    public function setDtoIncludeNestedUpdate(bool $dtoIncludeNestedUpdate): self
    {
        $this->dtoIncludeNestedUpdate = $dtoIncludeNestedUpdate;
        return $this;
    }

    public function getDtoMaxNestingDepth(): int
    {
        return $this->dtoMaxNestingDepth;
    }

    public function setDtoMaxNestingDepth(int $dtoMaxNestingDepth): self
    {
        $this->dtoMaxNestingDepth = $dtoMaxNestingDepth;
        return $this;
    }

    public function getDtoExcludedProperties(): ?array
    {
        return $this->dtoExcludedProperties;
    }

    public function setDtoExcludedProperties(?array $dtoExcludedProperties): self
    {
        $this->dtoExcludedProperties = $dtoExcludedProperties;
        return $this;
    }

    public function getCrossFieldValidationRules(): ?array
    {
        return $this->crossFieldValidationRules;
    }

    public function setCrossFieldValidationRules(?array $crossFieldValidationRules): self
    {
        $this->crossFieldValidationRules = $crossFieldValidationRules;
        return $this;
    }

    public function getUniqueConstraints(): ?array
    {
        return $this->uniqueConstraints;
    }

    public function setUniqueConstraints(?array $uniqueConstraints): self
    {
        $this->uniqueConstraints = $uniqueConstraints;
        return $this;
    }

    public function getBusinessRules(): ?array
    {
        return $this->businessRules;
    }

    public function setBusinessRules(?array $businessRules): self
    {
        $this->businessRules = $businessRules;
        return $this;
    }

    public function getValidationMessages(): ?array
    {
        return $this->validationMessages;
    }

    public function setValidationMessages(?array $validationMessages): self
    {
        $this->validationMessages = $validationMessages;
        return $this;
    }
}
