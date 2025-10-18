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
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ORM\Entity(repositoryClass: GeneratorEntityRepository::class)]
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
class GeneratorEntity
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private Uuid $id;

    // ====================================
    // BASIC INFORMATION (5 fields)
    // ====================================

    #[ORM\Column(length: 100, unique: true)]
    private string $entityName;           // "Contact" (PascalCase)

    #[ORM\Column(length: 100)]
    private string $entityLabel;          // "Contact"

    #[ORM\Column(length: 100)]
    private string $pluralLabel;          // "Contacts"

    #[ORM\Column(length: 50)]
    private string $icon;                 // "bi-person"

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // ====================================
    // CANVAS POSITIONING (2 fields)
    // ====================================

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    private int $canvasX = 100;

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    private int $canvasY = 100;

    // ====================================
    // MULTI-TENANCY (1 field)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $hasOrganization = true;

    // ====================================
    // API CONFIGURATION (8 fields)
    // ====================================

    #[ORM\Column(options: ['default' => false])]
    private bool $apiEnabled = false;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiOperations = null;    // ['GetCollection', 'Get', 'Post', 'Put', 'Delete']

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $apiSecurity = null;     // "is_granted('ROLE_USER')"

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiNormalizationContext = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiDenormalizationContext = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiDefaultOrder = null;  // {"name": "asc"}

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiSearchableFields = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $apiFilterableFields = null;

    // ====================================
    // SECURITY (2 fields)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $voterEnabled = true;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $voterAttributes = null;  // ['VIEW', 'EDIT', 'DELETE', 'CREATE']

    // ====================================
    // FORM (1 field)
    // ====================================

    #[ORM\Column(length: 255, options: ['default' => 'bootstrap_5_layout.html.twig'])]
    private string $formTheme = 'bootstrap_5_layout.html.twig';

    // ====================================
    // UI TEMPLATES (3 fields)
    // ====================================

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customIndexTemplate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customFormTemplate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $customShowTemplate = null;

    // ====================================
    // NAVIGATION (2 fields)
    // ====================================

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $menuGroup = null;       // "CRM", "System", "Reports"

    #[ORM\Column(type: 'integer', options: ['default' => 100])]
    private int $menuOrder = 100;

    // ====================================
    // TESTING (1 field)
    // ====================================

    #[ORM\Column(options: ['default' => true])]
    private bool $testEnabled = true;

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
    private Collection $properties;

    // ====================================
    // GENERATION STATUS (3 fields)
    // ====================================

    #[ORM\Column(options: ['default' => false])]
    private bool $isGenerated = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastGeneratedAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastGenerationLog = null;

    // ====================================
    // AUDIT (2 fields)
    // ====================================

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
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

    // Getters and setters
    public function getId(): Uuid { return $this->id; }
    public function getEntityName(): string { return $this->entityName; }
    public function setEntityName(string $entityName): self { $this->entityName = $entityName; return $this; }
    public function getEntityLabel(): string { return $this->entityLabel; }
    public function setEntityLabel(string $entityLabel): self { $this->entityLabel = $entityLabel; return $this; }
    public function getPluralLabel(): string { return $this->pluralLabel; }
    public function setPluralLabel(string $pluralLabel): self { $this->pluralLabel = $pluralLabel; return $this; }
    public function getIcon(): string { return $this->icon; }
    public function setIcon(string $icon): self { $this->icon = $icon; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getCanvasX(): int { return $this->canvasX; }
    public function setCanvasX(int $canvasX): self { $this->canvasX = $canvasX; return $this; }
    public function getCanvasY(): int { return $this->canvasY; }
    public function setCanvasY(int $canvasY): self { $this->canvasY = $canvasY; return $this; }
    public function isHasOrganization(): bool { return $this->hasOrganization; }
    public function setHasOrganization(bool $hasOrganization): self { $this->hasOrganization = $hasOrganization; return $this; }
    public function isApiEnabled(): bool { return $this->apiEnabled; }
    public function setApiEnabled(bool $apiEnabled): self { $this->apiEnabled = $apiEnabled; return $this; }
    public function getApiOperations(): ?array { return $this->apiOperations; }
    public function setApiOperations(?array $apiOperations): self { $this->apiOperations = $apiOperations; return $this; }
    public function getApiSecurity(): ?string { return $this->apiSecurity; }
    public function setApiSecurity(?string $apiSecurity): self { $this->apiSecurity = $apiSecurity; return $this; }
    public function getApiNormalizationContext(): ?string { return $this->apiNormalizationContext; }
    public function setApiNormalizationContext(?string $apiNormalizationContext): self { $this->apiNormalizationContext = $apiNormalizationContext; return $this; }
    public function getApiDenormalizationContext(): ?string { return $this->apiDenormalizationContext; }
    public function setApiDenormalizationContext(?string $apiDenormalizationContext): self { $this->apiDenormalizationContext = $apiDenormalizationContext; return $this; }
    public function getApiDefaultOrder(): ?array { return $this->apiDefaultOrder; }
    public function setApiDefaultOrder(?array $apiDefaultOrder): self { $this->apiDefaultOrder = $apiDefaultOrder; return $this; }
    public function getApiSearchableFields(): ?array { return $this->apiSearchableFields; }
    public function setApiSearchableFields(?array $apiSearchableFields): self { $this->apiSearchableFields = $apiSearchableFields; return $this; }
    public function getApiFilterableFields(): ?array { return $this->apiFilterableFields; }
    public function setApiFilterableFields(?array $apiFilterableFields): self { $this->apiFilterableFields = $apiFilterableFields; return $this; }
    public function isVoterEnabled(): bool { return $this->voterEnabled; }
    public function setVoterEnabled(bool $voterEnabled): self { $this->voterEnabled = $voterEnabled; return $this; }
    public function getVoterAttributes(): ?array { return $this->voterAttributes; }
    public function setVoterAttributes(?array $voterAttributes): self { $this->voterAttributes = $voterAttributes; return $this; }
    public function getFormTheme(): string { return $this->formTheme; }
    public function setFormTheme(string $formTheme): self { $this->formTheme = $formTheme; return $this; }
    public function getCustomIndexTemplate(): ?string { return $this->customIndexTemplate; }
    public function setCustomIndexTemplate(?string $customIndexTemplate): self { $this->customIndexTemplate = $customIndexTemplate; return $this; }
    public function getCustomFormTemplate(): ?string { return $this->customFormTemplate; }
    public function setCustomFormTemplate(?string $customFormTemplate): self { $this->customFormTemplate = $customFormTemplate; return $this; }
    public function getCustomShowTemplate(): ?string { return $this->customShowTemplate; }
    public function setCustomShowTemplate(?string $customShowTemplate): self { $this->customShowTemplate = $customShowTemplate; return $this; }
    public function getMenuGroup(): ?string { return $this->menuGroup; }
    public function setMenuGroup(?string $menuGroup): self { $this->menuGroup = $menuGroup; return $this; }
    public function getMenuOrder(): int { return $this->menuOrder; }
    public function setMenuOrder(int $menuOrder): self { $this->menuOrder = $menuOrder; return $this; }
    public function isTestEnabled(): bool { return $this->testEnabled; }
    public function setTestEnabled(bool $testEnabled): self { $this->testEnabled = $testEnabled; return $this; }
    public function getProperties(): Collection { return $this->properties; }
    public function addProperty(GeneratorProperty $property): self { if (!$this->properties->contains($property)) { $this->properties->add($property); $property->setEntity($this); } return $this; }
    public function removeProperty(GeneratorProperty $property): self { if ($this->properties->removeElement($property)) { if ($property->getEntity() === $this) { $property->setEntity(null); } } return $this; }
    public function isGenerated(): bool { return $this->isGenerated; }
    public function setIsGenerated(bool $isGenerated): self { $this->isGenerated = $isGenerated; return $this; }
    public function getLastGeneratedAt(): ?\DateTimeImmutable { return $this->lastGeneratedAt; }
    public function setLastGeneratedAt(?\DateTimeImmutable $lastGeneratedAt): self { $this->lastGeneratedAt = $lastGeneratedAt; return $this; }
    public function getLastGenerationLog(): ?string { return $this->lastGenerationLog; }
    public function setLastGenerationLog(?string $lastGenerationLog): self { $this->lastGenerationLog = $lastGenerationLog; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
