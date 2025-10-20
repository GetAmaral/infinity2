<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use App\Repository\ProfileTemplateFieldRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;

/**
 * ProfileTemplateField Entity
 *
 * Defines individual fields within a ProfileTemplate.
 * Supports rich field configuration including validation, UI rendering,
 * conditional visibility, and data type definitions.
 *
 * Features (2025 Best Practices):
 * - Flexible field types (text, email, phone, date, select, multi-select, etc.)
 * - Field-level validation rules
 * - Conditional visibility based on other field values
 * - Field dependencies and relationships
 * - Custom regex patterns for validation
 * - Rich text and markdown support
 * - File upload configuration
 * - Field grouping and sectioning
 * - Inline help text and tooltips
 * - Placeholder and default values
 * - Field masking for sensitive data
 * - Auto-completion suggestions
 * - Field-level permissions
 *
 * @see ProfileTemplate
 */
#[ORM\Entity(repositoryClass: ProfileTemplateFieldRepository::class)]
#[ORM\Table(name: 'profile_template_field')]
#[ORM\Index(name: 'idx_field_template', columns: ['profile_template_id'])]
#[ORM\Index(name: 'idx_field_name', columns: ['field_name'])]
#[ORM\Index(name: 'idx_field_type', columns: ['field_type'])]
#[ORM\Index(name: 'idx_field_required', columns: ['required'])]
#[ORM\Index(name: 'idx_field_active', columns: ['active'])]
#[ORM\Index(name: 'idx_field_order', columns: ['field_order'])]
#[ORM\Index(name: 'idx_field_section', columns: ['section'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    shortName: 'ProfileTemplateField',
    description: 'Field definition within a ProfileTemplate',
    normalizationContext: [
        'groups' => ['profile_template_field:read'],
        'swagger_definition_name' => 'Read'
    ],
    denormalizationContext: [
        'groups' => ['profile_template_field:write'],
        'swagger_definition_name' => 'Write'
    ],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile_template_field:read', 'profile_template_field:read:full']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile_template_field:read']],
            paginationEnabled: true,
            paginationItemsPerPage: 50
        ),
        new Post(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['profile_template_field:write', 'profile_template_field:create']],
            validationContext: ['groups' => ['Default', 'profile_template_field:create']]
        ),
        new Put(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['profile_template_field:write']],
            validationContext: ['groups' => ['Default', 'profile_template_field:update']]
        ),
        new Patch(
            security: "is_granted('ROLE_ADMIN')",
            denormalizationContext: ['groups' => ['profile_template_field:write']],
            validationContext: ['groups' => ['Default', 'profile_template_field:update']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        )
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 50,
    paginationMaximumItemsPerPage: 200,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true,
    order: ['fieldOrder' => 'ASC']
)]
#[ApiFilter(SearchFilter::class, properties: [
    'fieldName' => 'partial',
    'fieldLabel' => 'partial',
    'fieldType' => 'exact',
    'section' => 'exact'
])]
#[ApiFilter(BooleanFilter::class, properties: ['required', 'active', 'readonly', 'searchable', 'sensitive'])]
#[ApiFilter(OrderFilter::class, properties: [
    'fieldOrder',
    'fieldLabel',
    'fieldType',
    'createdAt'
], arguments: ['orderParameterName' => 'order'])]
class ProfileTemplateField extends EntityBase
{
    // ===== RELATIONSHIP =====

    #[ORM\ManyToOne(targetEntity: ProfileTemplate::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Profile template is required')]
    #[Groups(['profile_template_field:read', 'profile_template_field:create'])]
    #[ApiProperty(
        description: 'Profile template this field belongs to',
        readableLink: true,
        writableLink: false
    )]
    private ProfileTemplate $profileTemplate;

    // ===== BASIC INFORMATION =====

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Field name is required', groups: ['profile_template_field:create'])]
    #[Assert\Regex(
        pattern: '/^[a-z][a-zA-Z0-9]*$/',
        message: 'Field name must be in camelCase (e.g., firstName, emailAddress)'
    )]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write', 'profile_template:read'])]
    #[ApiProperty(
        description: 'Field name in camelCase (e.g., "emailAddress", "phoneNumber")',
        example: 'emailAddress',
        openapiContext: ['minLength' => 2, 'maxLength' => 100, 'pattern' => '^[a-z][a-zA-Z0-9]*$']
    )]
    private string $fieldName;

    #[ORM\Column(type: Types::STRING, length: 150)]
    #[Assert\NotBlank(message: 'Field label is required')]
    #[Assert\Length(min: 1, max: 150)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write', 'profile_template:read'])]
    #[ApiProperty(
        description: 'Human-readable field label displayed in UI',
        example: 'Email Address',
        openapiContext: ['minLength' => 1, 'maxLength' => 150]
    )]
    private string $fieldLabel;

    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank(message: 'Field type is required')]
    #[Assert\Choice(
        choices: [
            'text', 'email', 'phone', 'url', 'number', 'decimal', 'currency',
            'date', 'datetime', 'time', 'boolean', 'textarea', 'richtext', 'markdown',
            'select', 'multiselect', 'radio', 'checkbox', 'file', 'image',
            'country', 'state', 'city', 'timezone', 'locale', 'color', 'json'
        ],
        message: 'Invalid field type'
    )]
    #[Groups(['profile_template_field:read', 'profile_template_field:write', 'profile_template:read'])]
    #[ApiProperty(
        description: 'Field data type determining validation and UI rendering',
        example: 'email',
        openapiContext: [
            'enum' => [
                'text', 'email', 'phone', 'url', 'number', 'decimal', 'currency',
                'date', 'datetime', 'time', 'boolean', 'textarea', 'richtext', 'markdown',
                'select', 'multiselect', 'radio', 'checkbox', 'file', 'image',
                'country', 'state', 'city', 'timezone', 'locale', 'color', 'json'
            ]
        ]
    )]
    private string $fieldType;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'Description cannot exceed {{ limit }} characters')]
    #[Groups(['profile_template_field:read', 'profile_template_field:write', 'profile_template:read'])]
    #[ApiProperty(
        description: 'Field description explaining its purpose',
        example: 'Primary email address for communication',
        openapiContext: ['maxLength' => 500]
    )]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 9999, notInRangeMessage: 'Field order must be between {{ min }} and {{ max }}')]
    #[Groups(['profile_template_field:read', 'profile_template_field:write', 'profile_template:read'])]
    #[ApiProperty(
        description: 'Display order within template (lower numbers appear first)',
        example: 10,
        openapiContext: ['minimum' => 0, 'maximum' => 9999]
    )]
    private int $fieldOrder = 0;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write', 'profile_template:read'])]
    #[ApiProperty(
        description: 'Section grouping for organizing fields',
        example: 'Contact Information',
        openapiContext: ['maxLength' => 100]
    )]
    private ?string $section = null;

    // ===== VALIDATION =====

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template_field:read', 'profile_template_field:write', 'profile_template:read'])]
    #[ApiProperty(
        description: 'Field is required and must have a value',
        example: true
    )]
    private bool $required = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: 'Min length must be positive')]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Minimum length for text fields',
        example: 5,
        openapiContext: ['minimum' => 0]
    )]
    private ?int $minLength = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: 'Max length must be positive')]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Maximum length for text fields',
        example: 255,
        openapiContext: ['minimum' => 0]
    )]
    private ?int $maxLength = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Custom regex pattern for validation',
        example: '^[A-Z][a-z]+$',
        openapiContext: ['maxLength' => 255]
    )]
    private ?string $regexPattern = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Error message when regex validation fails',
        example: 'Must start with uppercase letter',
        openapiContext: ['maxLength' => 255]
    )]
    private ?string $regexMessage = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Minimum value for numeric fields',
        example: '0.00',
        openapiContext: ['type' => 'number', 'format' => 'decimal']
    )]
    private ?string $minValue = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Maximum value for numeric fields',
        example: '100000.00',
        openapiContext: ['type' => 'number', 'format' => 'decimal']
    )]
    private ?string $maxValue = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Additional validation rules as key-value pairs',
        example: '{"email": true, "unique": true}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $validationRules = null;

    // ===== OPTIONS (for select, radio, checkbox fields) =====

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Options for select/radio/checkbox fields',
        example: '[{"value": "option1", "label": "Option 1"}, {"value": "option2", "label": "Option 2"}]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'object']]
    )]
    private ?array $options = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Allow multiple selection for select fields',
        example: false
    )]
    private bool $multipleSelect = false;

    // ===== DEFAULT VALUES =====

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Default value for new profiles',
        example: 'default@example.com',
        openapiContext: ['type' => 'string']
    )]
    private ?string $defaultValue = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Placeholder text shown in empty field',
        example: 'Enter your email address',
        openapiContext: ['maxLength' => 255]
    )]
    private ?string $placeholder = null;

    // ===== UI CONFIGURATION =====

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Help text displayed below field',
        example: 'We will never share your email with anyone',
        openapiContext: ['type' => 'string']
    )]
    private ?string $helpText = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Tooltip text shown on hover',
        example: 'Your primary contact email',
        openapiContext: ['maxLength' => 255]
    )]
    private ?string $tooltip = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Regex(
        pattern: '/^bi-[a-z0-9-]+$/',
        message: 'Icon must be a valid Bootstrap icon'
    )]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Bootstrap icon for field visualization',
        example: 'bi-envelope',
        openapiContext: ['pattern' => '^bi-[a-z0-9-]+$']
    )]
    private ?string $icon = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(
        choices: ['full', 'half', 'third', 'quarter', 'two-thirds'],
        message: 'Invalid width'
    )]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Field width in form layout',
        example: 'half',
        openapiContext: ['enum' => ['full', 'half', 'third', 'quarter', 'two-thirds']]
    )]
    private ?string $width = 'full';

    // ===== FIELD BEHAVIOR =====

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['profile_template_field:read', 'profile_template_field:write', 'profile_template:read'])]
    #[ApiProperty(
        description: 'Field is active and displayed',
        example: true
    )]
    private bool $active = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Field is read-only and cannot be edited',
        example: false
    )]
    private bool $readonly = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Field is searchable in profile search',
        example: true
    )]
    private bool $searchable = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Field contains sensitive data (masked in UI)',
        example: false
    )]
    private bool $sensitive = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Field value must be unique across all profiles',
        example: false
    )]
    private bool $unique = false;

    // ===== CONDITIONAL VISIBILITY =====

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Conditions for field visibility (JSON rules)',
        example: '{"field": "employmentType", "operator": "equals", "value": "employee"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $visibilityConditions = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Fields that this field depends on',
        example: '["employmentType", "department"]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'string']]
    )]
    private ?array $dependencies = null;

    // ===== AUTO-COMPLETION =====

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Enable auto-completion suggestions',
        example: false
    )]
    private bool $autocomplete = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'API endpoint for fetching autocomplete suggestions',
        example: '/api/autocomplete/departments',
        openapiContext: ['maxLength' => 255]
    )]
    private ?string $autocompleteSource = null;

    // ===== FILE UPLOAD (for file/image fields) =====

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Allowed file types for file/image fields',
        example: '["image/jpeg", "image/png", "application/pdf"]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'string']]
    )]
    private ?array $allowedFileTypes = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive(message: 'Max file size must be positive')]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Maximum file size in bytes',
        example: 5242880,
        openapiContext: ['minimum' => 0]
    )]
    private ?int $maxFileSize = null;

    // ===== METADATA =====

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile_template_field:read', 'profile_template_field:write'])]
    #[ApiProperty(
        description: 'Custom metadata for extensibility',
        example: '{"custom_key": "custom_value"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $metadata = null;

    // ===== CONSTRUCTOR =====

    public function __construct()
    {
        parent::__construct();
        $this->required = false;
        $this->active = true;
        $this->readonly = false;
        $this->searchable = false;
        $this->sensitive = false;
        $this->unique = false;
        $this->multipleSelect = false;
        $this->autocomplete = false;
        $this->fieldOrder = 0;
        $this->width = 'full';
    }

    // ===== DOMAIN LOGIC METHODS =====

    /**
     * Check if field is a select type
     */
    public function isSelectType(): bool
    {
        return in_array($this->fieldType, ['select', 'multiselect', 'radio', 'checkbox'], true);
    }

    /**
     * Check if field is a numeric type
     */
    public function isNumericType(): bool
    {
        return in_array($this->fieldType, ['number', 'decimal', 'currency'], true);
    }

    /**
     * Check if field is a date type
     */
    public function isDateType(): bool
    {
        return in_array($this->fieldType, ['date', 'datetime', 'time'], true);
    }

    /**
     * Check if field is a file type
     */
    public function isFileType(): bool
    {
        return in_array($this->fieldType, ['file', 'image'], true);
    }

    /**
     * Check if field has visibility conditions
     */
    public function hasVisibilityConditions(): bool
    {
        return $this->visibilityConditions !== null && !empty($this->visibilityConditions);
    }

    /**
     * Check if field has dependencies
     */
    public function hasDependencies(): bool
    {
        return $this->dependencies !== null && !empty($this->dependencies);
    }

    /**
     * Validate field value against rules
     */
    public function validateValue(mixed $value): bool
    {
        // Basic validation logic (can be extended)
        if ($this->required && empty($value)) {
            return false;
        }

        if ($this->unique && $value !== null) {
            // Uniqueness check would be done at repository level
            return true;
        }

        if ($this->regexPattern && is_string($value)) {
            return (bool) preg_match('/' . $this->regexPattern . '/', $value);
        }

        return true;
    }

    // ===== GETTERS & SETTERS =====

    public function getProfileTemplate(): ProfileTemplate
    {
        return $this->profileTemplate;
    }

    public function setProfileTemplate(?ProfileTemplate $profileTemplate): self
    {
        $this->profileTemplate = $profileTemplate;
        return $this;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function setFieldName(string $fieldName): self
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getFieldLabel(): string
    {
        return $this->fieldLabel;
    }

    public function setFieldLabel(string $fieldLabel): self
    {
        $this->fieldLabel = $fieldLabel;
        return $this;
    }

    public function getFieldType(): string
    {
        return $this->fieldType;
    }

    public function setFieldType(string $fieldType): self
    {
        $this->fieldType = $fieldType;
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

    public function getFieldOrder(): int
    {
        return $this->fieldOrder;
    }

    public function setFieldOrder(int $fieldOrder): self
    {
        $this->fieldOrder = $fieldOrder;
        return $this;
    }

    public function getSection(): ?string
    {
        return $this->section;
    }

    public function setSection(?string $section): self
    {
        $this->section = $section;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    public function getMinLength(): ?int
    {
        return $this->minLength;
    }

    public function setMinLength(?int $minLength): self
    {
        $this->minLength = $minLength;
        return $this;
    }

    public function getMaxLength(): ?int
    {
        return $this->maxLength;
    }

    public function setMaxLength(?int $maxLength): self
    {
        $this->maxLength = $maxLength;
        return $this;
    }

    public function getRegexPattern(): ?string
    {
        return $this->regexPattern;
    }

    public function setRegexPattern(?string $regexPattern): self
    {
        $this->regexPattern = $regexPattern;
        return $this;
    }

    public function getRegexMessage(): ?string
    {
        return $this->regexMessage;
    }

    public function setRegexMessage(?string $regexMessage): self
    {
        $this->regexMessage = $regexMessage;
        return $this;
    }

    public function getMinValue(): ?string
    {
        return $this->minValue;
    }

    public function setMinValue(?string $minValue): self
    {
        $this->minValue = $minValue;
        return $this;
    }

    public function getMaxValue(): ?string
    {
        return $this->maxValue;
    }

    public function setMaxValue(?string $maxValue): self
    {
        $this->maxValue = $maxValue;
        return $this;
    }

    public function getValidationRules(): ?array
    {
        return $this->validationRules;
    }

    public function setValidationRules(?array $validationRules): self
    {
        $this->validationRules = $validationRules;
        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    public function setOptions(?array $options): self
    {
        $this->options = $options;
        return $this;
    }

    public function isMultipleSelect(): bool
    {
        return $this->multipleSelect;
    }

    public function setMultipleSelect(bool $multipleSelect): self
    {
        $this->multipleSelect = $multipleSelect;
        return $this;
    }

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(?string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function setHelpText(?string $helpText): self
    {
        $this->helpText = $helpText;
        return $this;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function setTooltip(?string $tooltip): self
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function setWidth(?string $width): self
    {
        $this->width = $width;
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

    public function isReadonly(): bool
    {
        return $this->readonly;
    }

    public function setReadonly(bool $readonly): self
    {
        $this->readonly = $readonly;
        return $this;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function setSearchable(bool $searchable): self
    {
        $this->searchable = $searchable;
        return $this;
    }

    public function isSensitive(): bool
    {
        return $this->sensitive;
    }

    public function setSensitive(bool $sensitive): self
    {
        $this->sensitive = $sensitive;
        return $this;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): self
    {
        $this->unique = $unique;
        return $this;
    }

    public function getVisibilityConditions(): ?array
    {
        return $this->visibilityConditions;
    }

    public function setVisibilityConditions(?array $visibilityConditions): self
    {
        $this->visibilityConditions = $visibilityConditions;
        return $this;
    }

    public function getDependencies(): ?array
    {
        return $this->dependencies;
    }

    public function setDependencies(?array $dependencies): self
    {
        $this->dependencies = $dependencies;
        return $this;
    }

    public function isAutocomplete(): bool
    {
        return $this->autocomplete;
    }

    public function setAutocomplete(bool $autocomplete): self
    {
        $this->autocomplete = $autocomplete;
        return $this;
    }

    public function getAutocompleteSource(): ?string
    {
        return $this->autocompleteSource;
    }

    public function setAutocompleteSource(?string $autocompleteSource): self
    {
        $this->autocompleteSource = $autocompleteSource;
        return $this;
    }

    public function getAllowedFileTypes(): ?array
    {
        return $this->allowedFileTypes;
    }

    public function setAllowedFileTypes(?array $allowedFileTypes): self
    {
        $this->allowedFileTypes = $allowedFileTypes;
        return $this;
    }

    public function getMaxFileSize(): ?int
    {
        return $this->maxFileSize;
    }

    public function setMaxFileSize(?int $maxFileSize): self
    {
        $this->maxFileSize = $maxFileSize;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function __toString(): string
    {
        return $this->fieldLabel;
    }
}
