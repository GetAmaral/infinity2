<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TaxCategoryRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * TaxCategory Entity
 *
 * Manages tax categories for financial operations, product taxation, and compliance.
 * Supports multi-jurisdiction taxation with country/region specificity, priority-based
 * tax application, and time-based tax rate changes.
 *
 * Features:
 * - Multi-jurisdiction support (country, region, postal codes)
 * - Time-based tax rates (effective and expiration dates)
 * - Priority-based tax category application
 * - Tax exemption tracking
 * - Compound tax calculation support
 * - Audit trail for tax changes
 *
 * @package App\Entity
 */
#[ORM\Entity(repositoryClass: TaxCategoryRepository::class)]
#[ORM\Table(name: 'tax_category')]
#[ORM\Index(name: 'idx_tax_category_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_tax_category_code', columns: ['code'])]
#[ORM\Index(name: 'idx_tax_category_country', columns: ['country'])]
#[ORM\Index(name: 'idx_tax_category_region', columns: ['region'])]
#[ORM\Index(name: 'idx_tax_category_active', columns: ['active'])]
#[ORM\Index(name: 'idx_tax_category_default', columns: ['default_category'])]
#[ORM\Index(name: 'idx_tax_category_priority', columns: ['priority'])]
#[ORM\Index(name: 'idx_tax_category_effective_date', columns: ['effective_date'])]
#[ORM\Index(name: 'idx_tax_category_expiration_date', columns: ['expiration_date'])]
#[ORM\Index(name: 'idx_tax_category_tax_type', columns: ['tax_type'])]
#[ORM\UniqueConstraint(name: 'uniq_tax_category_code_org', columns: ['code', 'organization_id'])]
#[UniqueEntity(fields: ['code', 'organization'], message: 'A tax category with this code already exists in your organization.')]
#[ApiResource(
    shortName: 'TaxCategory',
    description: 'Tax categories for financial management, product taxation, and multi-jurisdiction compliance',
    normalizationContext: ['groups' => ['taxcategory:read']],
    denormalizationContext: ['groups' => ['taxcategory:write']],
    order: ['priority' => 'ASC', 'name' => 'ASC'],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['taxcategory:read', 'taxcategory:detail']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['taxcategory:read', 'taxcategory:list']]
        ),
        new Post(
            security: "is_granted('ROLE_DATA_ADMIN')",
            denormalizationContext: ['groups' => ['taxcategory:write', 'taxcategory:create']]
        ),
        new Put(
            security: "is_granted('ROLE_DATA_ADMIN')",
            denormalizationContext: ['groups' => ['taxcategory:write', 'taxcategory:update']]
        ),
        new Delete(
            security: "is_granted('ROLE_DATA_ADMIN')"
        ),
        new GetCollection(
            uriTemplate: '/tax-categories/active',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['taxcategory:read']],
            description: 'Get all active tax categories'
        ),
        new GetCollection(
            uriTemplate: '/tax-categories/by-country/{country}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['taxcategory:read']],
            description: 'Get tax categories by country code'
        ),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: [
    'name' => 'partial',
    'code' => 'exact',
    'country' => 'exact',
    'region' => 'partial',
    'taxType' => 'exact',
    'categoryName' => 'partial'
])]
#[ApiFilter(BooleanFilter::class, properties: ['active', 'defaultCategory', 'exemptCategory', 'compoundTax'])]
#[ApiFilter(OrderFilter::class, properties: ['name', 'code', 'priority', 'taxRate', 'effectiveDate', 'createdAt'])]
#[ApiFilter(DateFilter::class, properties: ['effectiveDate', 'expirationDate', 'createdAt', 'updatedAt'])]
class TaxCategory extends EntityBase
{
    /**
     * Tax category name (human-readable)
     *
     * Example: "Standard VAT", "Sales Tax - California", "GST 18%"
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Tax category name is required')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Tax category name must be at least {{ limit }} characters',
        maxMessage: 'Tax category name cannot exceed {{ limit }} characters'
    )]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list'])]
    private string $name = '';

    /**
     * Unique tax category code (uppercase alphanumeric)
     *
     * Example: "VAT_STANDARD", "SALES_CA", "GST_18"
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: 'Tax category code is required')]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(
        pattern: '/^[A-Z0-9_-]+$/',
        message: 'Tax code must contain only uppercase letters, numbers, underscores, and hyphens'
    )]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list'])]
    private string $code = '';

    /**
     * Descriptive category name for classification
     *
     * Example: "Value Added Tax", "Sales Tax", "Goods and Services Tax"
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list'])]
    private ?string $categoryName = null;

    /**
     * Tax rate as decimal (0.00 to 100.00)
     *
     * Example: 20.00 for 20% VAT, 8.50 for 8.5% sales tax
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(
        min: 0,
        max: 100,
        notInRangeMessage: 'Tax rate must be between {{ min }}% and {{ max }}%'
    )]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list', 'taxcategory:detail'])]
    private ?string $taxRate = null;

    /**
     * Tax type classification
     *
     * Examples: VAT, SALES, GST, EXCISE, CUSTOMS, SERVICE, LUXURY, ENVIRONMENTAL
     */
    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(
        choices: ['VAT', 'SALES', 'GST', 'EXCISE', 'CUSTOMS', 'SERVICE', 'LUXURY', 'ENVIRONMENTAL', 'IMPORT', 'EXPORT', 'OTHER'],
        message: 'Invalid tax type selected'
    )]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list'])]
    private ?string $taxType = null;

    /**
     * ISO 3166-1 alpha-2 country code
     *
     * Example: "US", "GB", "DE", "FR", "BR"
     */
    #[ORM\Column(type: Types::STRING, length: 2, nullable: true)]
    #[Assert\Length(min: 2, max: 2)]
    #[Assert\Regex(
        pattern: '/^[A-Z]{2}$/',
        message: 'Country code must be 2 uppercase letters (ISO 3166-1 alpha-2)'
    )]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list'])]
    private ?string $country = null;

    /**
     * Region/state/province code or name
     *
     * Example: "CA" for California, "NY" for New York, "ON" for Ontario
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $region = null;

    /**
     * Postal/ZIP codes where this tax applies (comma-separated or pattern)
     *
     * Example: "90001,90002,90003" or "900*" for pattern matching
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $postalCodes = null;

    /**
     * Detailed description of tax category and its application
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $description = null;

    /**
     * Whether this tax category is currently active
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list'])]
    private bool $active = true;

    /**
     * Whether this is the default tax category for the organization
     *
     * Only one tax category should be marked as default per organization/country
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list'])]
    private bool $defaultCategory = false;

    /**
     * Priority for tax application (lower number = higher priority)
     *
     * Used when multiple tax categories could apply to determine which takes precedence
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 100])]
    #[Assert\Range(
        min: 1,
        max: 1000,
        notInRangeMessage: 'Priority must be between {{ min }} and {{ max }}'
    )]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:list'])]
    private int $priority = 100;

    /**
     * Date when this tax rate becomes effective
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?\DateTimeImmutable $effectiveDate = null;

    /**
     * Date when this tax rate expires (null = no expiration)
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?\DateTimeImmutable $expirationDate = null;

    /**
     * Whether this is a tax-exempt category (0% tax rate)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private bool $exemptCategory = false;

    /**
     * Tax exemption reason or legal reference
     *
     * Example: "Educational materials exempt under statute XYZ"
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $exemptionReason = null;

    /**
     * Whether this tax is compounded (calculated on top of other taxes)
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private bool $compoundTax = false;

    /**
     * Tax authority or government agency name
     *
     * Example: "IRS", "HMRC", "Receita Federal"
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $taxAuthority = null;

    /**
     * Tax registration or identification number
     *
     * Example: VAT number, GST registration number
     */
    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $taxRegistrationNumber = null;

    /**
     * Legal reference or statute citation
     *
     * Example: "IRC Section 1234", "VAT Act 1994 Schedule 7"
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $legalReference = null;

    /**
     * URL to official tax authority documentation
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    #[Assert\Length(max: 500)]
    #[Assert\Url(message: 'Please enter a valid URL')]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $documentationUrl = null;

    /**
     * Internal notes for tax category management (not visible to customers)
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $internalNotes = null;

    /**
     * Minimum transaction amount for tax to apply (null = no minimum)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Minimum amount must be zero or positive')]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $minimumAmount = null;

    /**
     * Maximum transaction amount for tax to apply (null = no maximum)
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Maximum amount must be zero or positive')]
    #[Groups(['taxcategory:read', 'taxcategory:write', 'taxcategory:detail'])]
    private ?string $maximumAmount = null;

    /**
     * Organization that owns this tax category
     */
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['taxcategory:read'])]
    private Organization $organization;

    public function __construct()
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return sprintf('%s (%s%%) - %s', $this->name, $this->taxRate ?? '0', $this->code);
    }

    // ========== Getters and Setters ==========

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = strtoupper($code);
        return $this;
    }

    public function getCategoryName(): ?string
    {
        return $this->categoryName;
    }

    public function setCategoryName(?string $categoryName): self
    {
        $this->categoryName = $categoryName;
        return $this;
    }

    public function getTaxRate(): ?string
    {
        return $this->taxRate;
    }

    public function setTaxRate(?string $taxRate): self
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    public function getTaxType(): ?string
    {
        return $this->taxType;
    }

    public function setTaxType(?string $taxType): self
    {
        $this->taxType = $taxType;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country ? strtoupper($country) : null;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(?string $region): self
    {
        $this->region = $region;
        return $this;
    }

    public function getPostalCodes(): ?string
    {
        return $this->postalCodes;
    }

    public function setPostalCodes(?string $postalCodes): self
    {
        $this->postalCodes = $postalCodes;
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

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function isDefaultCategory(): bool
    {
        return $this->defaultCategory;
    }

    public function setDefaultCategory(bool $defaultCategory): self
    {
        $this->defaultCategory = $defaultCategory;
        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getEffectiveDate(): ?\DateTimeImmutable
    {
        return $this->effectiveDate;
    }

    public function setEffectiveDate(?\DateTimeImmutable $effectiveDate): self
    {
        $this->effectiveDate = $effectiveDate;
        return $this;
    }

    public function getExpirationDate(): ?\DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?\DateTimeImmutable $expirationDate): self
    {
        $this->expirationDate = $expirationDate;
        return $this;
    }

    public function isExemptCategory(): bool
    {
        return $this->exemptCategory;
    }

    public function setExemptCategory(bool $exemptCategory): self
    {
        $this->exemptCategory = $exemptCategory;
        return $this;
    }

    public function getExemptionReason(): ?string
    {
        return $this->exemptionReason;
    }

    public function setExemptionReason(?string $exemptionReason): self
    {
        $this->exemptionReason = $exemptionReason;
        return $this;
    }

    public function isCompoundTax(): bool
    {
        return $this->compoundTax;
    }

    public function setCompoundTax(bool $compoundTax): self
    {
        $this->compoundTax = $compoundTax;
        return $this;
    }

    public function getTaxAuthority(): ?string
    {
        return $this->taxAuthority;
    }

    public function setTaxAuthority(?string $taxAuthority): self
    {
        $this->taxAuthority = $taxAuthority;
        return $this;
    }

    public function getTaxRegistrationNumber(): ?string
    {
        return $this->taxRegistrationNumber;
    }

    public function setTaxRegistrationNumber(?string $taxRegistrationNumber): self
    {
        $this->taxRegistrationNumber = $taxRegistrationNumber;
        return $this;
    }

    public function getLegalReference(): ?string
    {
        return $this->legalReference;
    }

    public function setLegalReference(?string $legalReference): self
    {
        $this->legalReference = $legalReference;
        return $this;
    }

    public function getDocumentationUrl(): ?string
    {
        return $this->documentationUrl;
    }

    public function setDocumentationUrl(?string $documentationUrl): self
    {
        $this->documentationUrl = $documentationUrl;
        return $this;
    }

    public function getInternalNotes(): ?string
    {
        return $this->internalNotes;
    }

    public function setInternalNotes(?string $internalNotes): self
    {
        $this->internalNotes = $internalNotes;
        return $this;
    }

    public function getMinimumAmount(): ?string
    {
        return $this->minimumAmount;
    }

    public function setMinimumAmount(?string $minimumAmount): self
    {
        $this->minimumAmount = $minimumAmount;
        return $this;
    }

    public function getMaximumAmount(): ?string
    {
        return $this->maximumAmount;
    }

    public function setMaximumAmount(?string $maximumAmount): self
    {
        $this->maximumAmount = $maximumAmount;
        return $this;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    // ========== Business Logic Methods ==========

    /**
     * Check if this tax category is currently valid based on effective/expiration dates
     */
    public function isCurrentlyValid(): bool
    {
        $now = new \DateTimeImmutable();

        if ($this->effectiveDate !== null && $this->effectiveDate > $now) {
            return false;
        }

        if ($this->expirationDate !== null && $this->expirationDate < $now) {
            return false;
        }

        return $this->active;
    }

    /**
     * Calculate tax amount for a given base amount
     */
    public function calculateTaxAmount(float $baseAmount): float
    {
        if ($this->taxRate === null || $this->exemptCategory) {
            return 0.0;
        }

        if ($this->minimumAmount !== null && $baseAmount < (float)$this->minimumAmount) {
            return 0.0;
        }

        if ($this->maximumAmount !== null && $baseAmount > (float)$this->maximumAmount) {
            return 0.0;
        }

        return round($baseAmount * ((float)$this->taxRate / 100), 2);
    }

    /**
     * Get display label for this tax category
     */
    public function getDisplayLabel(): string
    {
        $label = $this->name;

        if ($this->taxRate !== null) {
            $label .= sprintf(' (%s%%)', $this->taxRate);
        }

        if ($this->country !== null) {
            $label .= ' - ' . $this->country;
        }

        return $label;
    }
}
