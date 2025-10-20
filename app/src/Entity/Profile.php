<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProfileRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Doctrine\Orm\Filter\DateFilter;
use ApiPlatform\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Profile Entity
 *
 * Extended user profile information for CRM functionality.
 * Separated from User entity to optimize performance and follow single responsibility principle.
 *
 * Features:
 * - Contact Information (address, social media)
 * - Professional Information (bio, expertise, certifications)
 * - Personal Information (pronouns, emergency contact)
 * - Privacy Settings (profile visibility)
 * - Performance Metrics (sales, targets)
 * - Communication Preferences
 * - Geographic Data
 *
 * @see User
 */
#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\Table(name: 'profile')]
#[ORM\Index(name: 'idx_profile_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_profile_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_profile_public', columns: ['public'])]
#[ORM\Index(name: 'idx_profile_active', columns: ['active'])]
#[ORM\Index(name: 'idx_profile_verified', columns: ['verified'])]
#[ORM\Index(name: 'idx_profile_country', columns: ['country'])]
#[ORM\Index(name: 'idx_profile_state', columns: ['state'])]
#[ORM\Index(name: 'idx_profile_city', columns: ['city'])]
#[ORM\Index(name: 'idx_profile_deleted_at', columns: ['deleted_at'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    shortName: 'Profile',
    description: 'User profile with extended CRM information',
    normalizationContext: [
        'groups' => ['profile:read'],
        'swagger_definition_name' => 'Read'
    ],
    denormalizationContext: [
        'groups' => ['profile:write'],
        'swagger_definition_name' => 'Write'
    ],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER') and (object.getUser() == user or is_granted('ROLE_ADMIN'))",
            normalizationContext: ['groups' => ['profile:read', 'profile:read:full']]
        ),
        new GetCollection(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile:read']]
        ),
        new Post(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['profile:write', 'profile:create']]
        ),
        new Put(
            security: "is_granted('ROLE_USER') and (object.getUser() == user or is_granted('ROLE_ADMIN'))",
            denormalizationContext: ['groups' => ['profile:write']]
        ),
        new Patch(
            security: "is_granted('ROLE_USER') and (object.getUser() == user or is_granted('ROLE_ADMIN'))",
            denormalizationContext: ['groups' => ['profile:write']]
        ),
        new Delete(
            security: "is_granted('ROLE_ADMIN')"
        ),
        // Public profile endpoint
        new Get(
            uriTemplate: '/profiles/{id}/public',
            security: "is_granted('PUBLIC_ACCESS') or is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['profile:read:public']]
        ),
        // Admin endpoint with audit information
        new GetCollection(
            uriTemplate: '/admin/profiles',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['profile:read', 'profile:read:full', 'audit:read']]
        )
    ],
    paginationEnabled: true,
    paginationItemsPerPage: 30,
    paginationMaximumItemsPerPage: 100,
    paginationClientEnabled: true,
    paginationClientItemsPerPage: true
)]
#[ApiFilter(SearchFilter::class, properties: [
    'firstName' => 'partial',
    'lastName' => 'partial',
    'displayName' => 'partial',
    'jobTitle' => 'partial',
    'company' => 'partial',
    'department' => 'partial',
    'city' => 'exact',
    'state' => 'exact',
    'country' => 'exact'
])]
#[ApiFilter(BooleanFilter::class, properties: ['active', 'public', 'verified'])]
#[ApiFilter(DateFilter::class, properties: ['createdAt', 'updatedAt', 'birthDate'])]
#[ApiFilter(OrderFilter::class, properties: [
    'firstName',
    'lastName',
    'createdAt',
    'updatedAt'
], arguments: ['orderParameterName' => 'order'])]
class Profile extends EntityBase
{
    // ===== RELATIONSHIPS =====

    #[ORM\OneToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Assert\NotNull(message: 'User is required')]
    #[Groups(['profile:read', 'profile:create'])]
    #[ApiProperty(
        description: 'User associated with this profile',
        readableLink: true,
        writableLink: false
    )]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Organization is required')]
    #[Groups(['profile:read'])]
    #[ApiProperty(
        description: 'Organization this profile belongs to',
        readableLink: true,
        writableLink: false
    )]
    private Organization $organization;

    // ===== BASIC INFORMATION =====

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 1, max: 100, minMessage: 'First name must be at least {{ limit }} characters', maxMessage: 'First name cannot exceed {{ limit }} characters')]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'First name of the user',
        example: 'John',
        openapiContext: ['minLength' => 1, 'maxLength' => 100]
    )]
    private string $firstName;

    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 1, max: 100, minMessage: 'Last name must be at least {{ limit }} characters', maxMessage: 'Last name cannot exceed {{ limit }} characters')]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Last name of the user',
        example: 'Doe',
        openapiContext: ['minLength' => 1, 'maxLength' => 100]
    )]
    private string $lastName;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Assert\Length(max: 100, maxMessage: 'Middle name cannot exceed {{ limit }} characters')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Middle name or initial',
        example: 'Michael',
        openapiContext: ['maxLength' => 100]
    )]
    private ?string $middleName = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Assert\Length(max: 150, maxMessage: 'Display name cannot exceed {{ limit }} characters')]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Preferred display name (overrides first + last name)',
        example: 'Johnny Doe',
        openapiContext: ['maxLength' => 150]
    )]
    private ?string $displayName = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(max: 20, maxMessage: 'Pronouns cannot exceed {{ limit }} characters')]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Preferred pronouns',
        example: 'he/him',
        openapiContext: ['maxLength' => 20]
    )]
    private ?string $pronouns = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Url(message: 'Avatar URL must be valid')]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Profile avatar/photo URL',
        example: 'https://cdn.example.com/avatars/johndoe.jpg',
        openapiContext: ['format' => 'uri', 'maxLength' => 255]
    )]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Date of birth',
        example: '1990-05-15',
        openapiContext: ['format' => 'date']
    )]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Choice(choices: ['male', 'female', 'non-binary', 'other', 'prefer-not-to-say'], message: 'Invalid gender value')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Gender identity',
        example: 'male',
        openapiContext: [
            'enum' => ['male', 'female', 'non-binary', 'other', 'prefer-not-to-say']
        ]
    )]
    private ?string $gender = null;

    // ===== CONTACT INFORMATION =====

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[Assert\Regex(pattern: '/^[+]?[0-9\s()-]+$/', message: 'Phone number format is invalid')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Primary phone number',
        example: '+1-555-123-4567',
        openapiContext: ['maxLength' => 30]
    )]
    private ?string $phone = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[Assert\Regex(pattern: '/^[+]?[0-9\s()-]+$/', message: 'Mobile phone number format is invalid')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Mobile phone number',
        example: '+1-555-987-6543',
        openapiContext: ['maxLength' => 30]
    )]
    private ?string $mobilePhone = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Street address line 1',
        example: '123 Main Street',
        openapiContext: ['maxLength' => 255]
    )]
    private ?string $address = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Street address line 2 (apt, suite, etc.)',
        example: 'Apt 4B',
        openapiContext: ['maxLength' => 255]
    )]
    private ?string $address2 = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'City',
        example: 'New York',
        openapiContext: ['maxLength' => 100]
    )]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'State/Province',
        example: 'NY',
        openapiContext: ['maxLength' => 100]
    )]
    private ?string $state = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Postal/ZIP code',
        example: '10001',
        openapiContext: ['maxLength' => 20]
    )]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::STRING, length: 2, nullable: true)]
    #[Assert\Length(min: 2, max: 2, exactMessage: 'Country code must be exactly {{ limit }} characters (ISO 3166-1 alpha-2)')]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Country code (ISO 3166-1 alpha-2)',
        example: 'US',
        openapiContext: ['minLength' => 2, 'maxLength' => 2]
    )]
    private ?string $country = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Timezone(message: 'Invalid timezone')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Timezone (IANA timezone database)',
        example: 'America/New_York',
        openapiContext: ['maxLength' => 50]
    )]
    private ?string $timezone = 'UTC';

    // ===== PROFESSIONAL INFORMATION =====

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Job title',
        example: 'Senior Sales Manager',
        openapiContext: ['maxLength' => 150]
    )]
    private ?string $jobTitle = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Department',
        example: 'Sales',
        openapiContext: ['maxLength' => 100]
    )]
    private ?string $department = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Company name',
        example: 'Acme Corporation',
        openapiContext: ['maxLength' => 150]
    )]
    private ?string $company = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Professional biography',
        example: 'Experienced sales professional with 10+ years in B2B software sales...'
    )]
    private ?string $bio = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'List of skills/expertise areas',
        example: '["Sales", "Negotiation", "CRM", "Lead Generation"]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'string']]
    )]
    private ?array $skills = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Professional certifications',
        example: '[{"name": "Certified Sales Professional", "issuer": "Sales Institute", "year": 2020}]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'object']]
    )]
    private ?array $certifications = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Languages spoken with proficiency levels',
        example: '[{"language": "English", "level": "native"}, {"language": "Spanish", "level": "intermediate"}]',
        openapiContext: ['type' => 'array', 'items' => ['type' => 'object']]
    )]
    private ?array $languages = null;

    // ===== SOCIAL MEDIA & WEB PRESENCE =====

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Url(message: 'LinkedIn URL must be valid')]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'LinkedIn profile URL',
        example: 'https://linkedin.com/in/johndoe',
        openapiContext: ['format' => 'uri', 'maxLength' => 255]
    )]
    private ?string $linkedinUrl = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Twitter/X username (without @)',
        example: 'johndoe',
        openapiContext: ['maxLength' => 100]
    )]
    private ?string $twitterUsername = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Url(message: 'Website URL must be valid')]
    #[Groups(['profile:read', 'profile:write', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Personal or professional website',
        example: 'https://johndoe.com',
        openapiContext: ['format' => 'uri', 'maxLength' => 255]
    )]
    private ?string $websiteUrl = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Additional social media links',
        example: '{"github": "https://github.com/johndoe", "stackoverflow": "https://stackoverflow.com/users/123"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $socialLinks = null;

    // ===== SETTINGS & PREFERENCES =====

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Profile is active and visible',
        example: true
    )]
    private bool $active = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Profile is publicly visible',
        example: false
    )]
    private bool $public = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Profile has been verified by administrator',
        example: false
    )]
    private bool $verified = false;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['default' => 'en'])]
    #[Assert\Locale(message: 'Invalid locale code')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Preferred locale/language code',
        example: 'en',
        openapiContext: ['maxLength' => 10]
    )]
    private ?string $locale = 'en';

    #[ORM\Column(type: Types::STRING, length: 3, nullable: true, options: ['default' => 'USD'])]
    #[Assert\Currency(message: 'Invalid currency code')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Preferred currency code (ISO 4217)',
        example: 'USD',
        openapiContext: ['minLength' => 3, 'maxLength' => 3]
    )]
    private ?string $currency = 'USD';

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['default' => 'Y-m-d'])]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Preferred date format',
        example: 'Y-m-d',
        openapiContext: ['maxLength' => 20]
    )]
    private ?string $dateFormat = 'Y-m-d';

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, options: ['default' => 'H:i'])]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Preferred time format',
        example: 'H:i',
        openapiContext: ['maxLength' => 20]
    )]
    private ?string $timeFormat = 'H:i';

    // ===== CRM SPECIFIC FIELDS =====

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Sales target must be positive or zero')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Sales target/quota amount',
        example: '100000.00',
        openapiContext: ['type' => 'number', 'format' => 'decimal']
    )]
    private ?string $salesTarget = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Sales achieved must be positive or zero')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Sales achieved to date',
        example: '75000.00',
        openapiContext: ['type' => 'number', 'format' => 'decimal']
    )]
    private ?string $salesAchieved = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: 'Commission rate must be between {{ min }}% and {{ max }}%')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Commission rate percentage',
        example: '5.50',
        openapiContext: ['type' => 'number', 'format' => 'decimal', 'minimum' => 0, 'maximum' => 100]
    )]
    private ?string $commissionRate = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Sales team name',
        example: 'Team Alpha',
        openapiContext: ['maxLength' => 100]
    )]
    private ?string $salesTeam = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    #[Assert\Choice(choices: ['employee', 'contractor', 'consultant', 'partner'], message: 'Invalid employment type')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Employment type',
        example: 'employee',
        openapiContext: ['enum' => ['employee', 'contractor', 'consultant', 'partner']]
    )]
    private ?string $employmentType = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Employment start date',
        example: '2020-01-15',
        openapiContext: ['format' => 'date']
    )]
    private ?\DateTimeImmutable $hireDate = null;

    // ===== EMERGENCY CONTACT =====

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Emergency contact name',
        example: 'Jane Doe',
        openapiContext: ['maxLength' => 150]
    )]
    private ?string $emergencyContactName = null;

    #[ORM\Column(type: Types::STRING, length: 30, nullable: true)]
    #[Assert\Regex(pattern: '/^[+]?[0-9\s()-]+$/', message: 'Emergency contact phone format is invalid')]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Emergency contact phone number',
        example: '+1-555-999-8888',
        openapiContext: ['maxLength' => 30]
    )]
    private ?string $emergencyContactPhone = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Emergency contact relationship',
        example: 'Spouse',
        openapiContext: ['maxLength' => 100]
    )]
    private ?string $emergencyContactRelationship = null;

    // ===== NOTIFICATION PREFERENCES =====

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Email notifications enabled',
        example: true
    )]
    private bool $emailNotifications = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'SMS notifications enabled',
        example: false
    )]
    private bool $smsNotifications = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Push notifications enabled',
        example: true
    )]
    private bool $pushNotifications = true;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Notification preferences by type',
        example: '{"tasks": true, "mentions": true, "reports": false}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $notificationPreferences = null;

    // ===== WORKING HOURS & AVAILABILITY =====

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Working hours schedule',
        example: '{"monday": {"start": "09:00", "end": "17:00"}, "tuesday": {"start": "09:00", "end": "17:00"}}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $workingHours = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['profile:read', 'profile:write'])]
    #[ApiProperty(
        description: 'Custom fields for organization-specific data',
        example: '{"employee_id": "EMP-12345", "cost_center": "CC-5678"}',
        openapiContext: ['type' => 'object']
    )]
    private ?array $customFields = null;

    // ===== SOFT DELETE =====

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['profile:read:full', 'audit:read'])]
    #[ApiProperty(
        description: 'Soft delete timestamp',
        readable: false
    )]
    private ?\DateTimeImmutable $deletedAt = null;

    // ===== COMPUTED PROPERTIES =====

    #[Groups(['profile:read', 'profile:read:public'])]
    #[ApiProperty(
        description: 'Full name (computed)',
        readable: true,
        writable: false
    )]
    public function getFullName(): string
    {
        if ($this->displayName) {
            return $this->displayName;
        }

        $parts = array_filter([
            $this->firstName ?? '',
            $this->middleName ?? '',
            $this->lastName ?? ''
        ]);

        return implode(' ', $parts);
    }

    #[Groups(['profile:read'])]
    #[ApiProperty(
        description: 'Sales target achievement percentage (computed)',
        readable: true,
        writable: false
    )]
    public function getSalesAchievementPercentage(): ?float
    {
        if (!$this->salesTarget || !$this->salesAchieved) {
            return null;
        }

        $target = (float) $this->salesTarget;
        if ($target === 0.0) {
            return null;
        }

        $achieved = (float) $this->salesAchieved;
        return round(($achieved / $target) * 100, 2);
    }

    #[Groups(['profile:read'])]
    #[ApiProperty(
        description: 'Profile is deleted (soft delete)',
        readable: true,
        writable: false
    )]
    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    // ===== CONSTRUCTOR =====

    public function __construct()
    {
        parent::__construct();
        $this->active = true;
        $this->public = false;
        $this->verified = false;
        $this->emailNotifications = true;
        $this->smsNotifications = false;
        $this->pushNotifications = true;
        $this->locale = 'en';
        $this->currency = 'USD';
        $this->dateFormat = 'Y-m-d';
        $this->timeFormat = 'H:i';
        $this->timezone = 'UTC';
    }

    // ===== GETTERS & SETTERS =====

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
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

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): self
    {
        $this->middleName = $middleName;
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getPronouns(): ?string
    {
        return $this->pronouns;
    }

    public function setPronouns(?string $pronouns): self
    {
        $this->pronouns = $pronouns;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): self
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getMobilePhone(): ?string
    {
        return $this->mobilePhone;
    }

    public function setMobilePhone(?string $mobilePhone): self
    {
        $this->mobilePhone = $mobilePhone;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getAddress2(): ?string
    {
        return $this->address2;
    }

    public function setAddress2(?string $address2): self
    {
        $this->address2 = $address2;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getState(): ?string
    {
        return $this->state;
    }

    public function setState(?string $state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(?string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): self
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): self
    {
        $this->department = $department;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio;
        return $this;
    }

    public function getSkills(): ?array
    {
        return $this->skills;
    }

    public function setSkills(?array $skills): self
    {
        $this->skills = $skills;
        return $this;
    }

    public function getCertifications(): ?array
    {
        return $this->certifications;
    }

    public function setCertifications(?array $certifications): self
    {
        $this->certifications = $certifications;
        return $this;
    }

    public function getLanguages(): ?array
    {
        return $this->languages;
    }

    public function setLanguages(?array $languages): self
    {
        $this->languages = $languages;
        return $this;
    }

    public function getLinkedinUrl(): ?string
    {
        return $this->linkedinUrl;
    }

    public function setLinkedinUrl(?string $linkedinUrl): self
    {
        $this->linkedinUrl = $linkedinUrl;
        return $this;
    }

    public function getTwitterUsername(): ?string
    {
        return $this->twitterUsername;
    }

    public function setTwitterUsername(?string $twitterUsername): self
    {
        $this->twitterUsername = $twitterUsername;
        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): self
    {
        $this->websiteUrl = $websiteUrl;
        return $this;
    }

    public function getSocialLinks(): ?array
    {
        return $this->socialLinks;
    }

    public function setSocialLinks(?array $socialLinks): self
    {
        $this->socialLinks = $socialLinks;
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

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): self
    {
        $this->public = $public;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(?string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getDateFormat(): ?string
    {
        return $this->dateFormat;
    }

    public function setDateFormat(?string $dateFormat): self
    {
        $this->dateFormat = $dateFormat;
        return $this;
    }

    public function getTimeFormat(): ?string
    {
        return $this->timeFormat;
    }

    public function setTimeFormat(?string $timeFormat): self
    {
        $this->timeFormat = $timeFormat;
        return $this;
    }

    public function getSalesTarget(): ?string
    {
        return $this->salesTarget;
    }

    public function setSalesTarget(?string $salesTarget): self
    {
        $this->salesTarget = $salesTarget;
        return $this;
    }

    public function getSalesAchieved(): ?string
    {
        return $this->salesAchieved;
    }

    public function setSalesAchieved(?string $salesAchieved): self
    {
        $this->salesAchieved = $salesAchieved;
        return $this;
    }

    public function getCommissionRate(): ?string
    {
        return $this->commissionRate;
    }

    public function setCommissionRate(?string $commissionRate): self
    {
        $this->commissionRate = $commissionRate;
        return $this;
    }

    public function getSalesTeam(): ?string
    {
        return $this->salesTeam;
    }

    public function setSalesTeam(?string $salesTeam): self
    {
        $this->salesTeam = $salesTeam;
        return $this;
    }

    public function getEmploymentType(): ?string
    {
        return $this->employmentType;
    }

    public function setEmploymentType(?string $employmentType): self
    {
        $this->employmentType = $employmentType;
        return $this;
    }

    public function getHireDate(): ?\DateTimeImmutable
    {
        return $this->hireDate;
    }

    public function setHireDate(?\DateTimeImmutable $hireDate): self
    {
        $this->hireDate = $hireDate;
        return $this;
    }

    public function getEmergencyContactName(): ?string
    {
        return $this->emergencyContactName;
    }

    public function setEmergencyContactName(?string $emergencyContactName): self
    {
        $this->emergencyContactName = $emergencyContactName;
        return $this;
    }

    public function getEmergencyContactPhone(): ?string
    {
        return $this->emergencyContactPhone;
    }

    public function setEmergencyContactPhone(?string $emergencyContactPhone): self
    {
        $this->emergencyContactPhone = $emergencyContactPhone;
        return $this;
    }

    public function getEmergencyContactRelationship(): ?string
    {
        return $this->emergencyContactRelationship;
    }

    public function setEmergencyContactRelationship(?string $emergencyContactRelationship): self
    {
        $this->emergencyContactRelationship = $emergencyContactRelationship;
        return $this;
    }

    public function isEmailNotifications(): bool
    {
        return $this->emailNotifications;
    }

    public function setEmailNotifications(bool $emailNotifications): self
    {
        $this->emailNotifications = $emailNotifications;
        return $this;
    }

    public function isSmsNotifications(): bool
    {
        return $this->smsNotifications;
    }

    public function setSmsNotifications(bool $smsNotifications): self
    {
        $this->smsNotifications = $smsNotifications;
        return $this;
    }

    public function isPushNotifications(): bool
    {
        return $this->pushNotifications;
    }

    public function setPushNotifications(bool $pushNotifications): self
    {
        $this->pushNotifications = $pushNotifications;
        return $this;
    }

    public function getNotificationPreferences(): ?array
    {
        return $this->notificationPreferences;
    }

    public function setNotificationPreferences(?array $notificationPreferences): self
    {
        $this->notificationPreferences = $notificationPreferences;
        return $this;
    }

    public function getWorkingHours(): ?array
    {
        return $this->workingHours;
    }

    public function setWorkingHours(?array $workingHours): self
    {
        $this->workingHours = $workingHours;
        return $this;
    }

    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    public function setCustomFields(?array $customFields): self
    {
        $this->customFields = $customFields;
        return $this;
    }

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): self
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function __toString(): string
    {
        return $this->getFullName();
    }
}
