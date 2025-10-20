# StepInput Entity Analysis Report

**Generated:** 2025-10-19
**Database:** PostgreSQL 18
**Framework:** Symfony 7.3 + API Platform 4.1
**Entity Path:** `/home/user/inf/app/src/Entity/StepInput.php`

---

## Executive Summary

The StepInput entity is **functionally complete but lacks API Platform exposure**. It follows most conventions but requires several critical improvements for production readiness:

1. **CRITICAL:** No ApiResource annotation - entity not exposed via API
2. **MISSING:** Organization field in entity (exists in DB but not mapped)
3. **INCOMPLETE:** Serialization groups need expansion
4. **MISSING:** Validation constraints are minimal
5. **MISSING:** Several recommended workflow validation fields

---

## 1. Current Entity Structure

### 1.1 Database Schema (PostgreSQL)

```sql
Table: step_input
├── id                 uuid (PK, UUIDv7)
├── created_at         timestamp NOT NULL
├── updated_at         timestamp NOT NULL
├── created_by_id      uuid (FK -> user)
├── updated_by_id      uuid (FK -> user)
├── organization_id    uuid NOT NULL (FK -> organization)  ⚠️ NOT MAPPED
├── step_id            uuid NOT NULL (FK -> step, CASCADE)
├── type               varchar NOT NULL (enum: fully_completed, not_completed_after_attempts, any)
├── name               varchar(255) NOT NULL
├── slug               varchar(255) NULL
└── prompt             text NULL
```

### 1.2 Entity Properties

| Property | Type | Validation | Groups | Notes |
|----------|------|------------|--------|-------|
| id | Uuid | Auto | - | UUIDv7 from EntityBase |
| createdAt | DateTimeImmutable | Auto | audit:read | From AuditTrait |
| updatedAt | DateTimeImmutable | Auto | audit:read | From AuditTrait |
| createdBy | User | Auto | audit:read | From AuditTrait |
| updatedBy | User | Auto | audit:read | From AuditTrait |
| step | Step | JoinColumn(nullable: false) | input:read | Parent step |
| type | InputType (enum) | - | input:read, input:write | Entry condition type |
| name | string(255) | NotBlank | input:read, input:write | Display name |
| slug | string(255) | - | input:read, input:write | URL-safe identifier |
| prompt | text | - | input:read, input:write | Additional context |
| connections | Collection\<StepConnection\> | - | input:read | Incoming connections |
| **organization** | **Organization** | **MISSING** | - | **⚠️ DB field not mapped** |

---

## 2. Issues Identified

### 2.1 CRITICAL Issues

#### Issue #1: No API Platform Exposure
**Severity:** CRITICAL
**Convention Violation:** API Platform best practices

**Problem:**
```php
// Current - NO ApiResource annotation
#[ORM\Entity(repositoryClass: StepInputRepository::class)]
class StepInput extends EntityBase
```

**Impact:**
- StepInput not accessible via REST API
- Cannot perform CRUD operations via API
- Frontend must use traditional controllers only
- Inconsistent with other entities (TreeFlow, Course, etc.)

**Solution Required:**
```php
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;

#[ApiResource(
    routePrefix: '/treeflows/inputs',
    normalizationContext: ['groups' => ['input:read']],
    denormalizationContext: ['groups' => ['input:write']],
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
#[ORM\Entity(repositoryClass: StepInputRepository::class)]
class StepInput extends EntityBase
```

---

#### Issue #2: Missing Organization Field Mapping
**Severity:** CRITICAL
**Convention Violation:** Multi-tenant architecture requirement

**Problem:**
- Database has `organization_id` column (NOT NULL)
- Entity does not map this field
- Violates multi-tenant isolation requirement
- All entities MUST have organization field per CLAUDE.md

**Current Database:**
```sql
organization_id   uuid   NO   (FK -> organization)
```

**Current Entity:**
```php
// MISSING - No organization field in StepInput.php
```

**Solution Required:**
```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
#[ORM\JoinColumn(nullable: false)]
#[Groups(['input:read'])]
protected Organization $organization;

public function getOrganization(): Organization
{
    return $this->organization;
}

public function setOrganization(Organization $organization): self
{
    $this->organization = $organization;
    return $this;
}
```

**Migration Needed:** NO (field already exists in database)

---

### 2.2 MAJOR Issues

#### Issue #3: Boolean Naming Convention Violation
**Severity:** MAJOR
**Convention Violation:** User specified "Boolean: 'required', 'visible' NOT 'isRequired'"

**Problem:**
- No boolean fields currently exist
- When adding validation fields, MUST use correct naming

**Correct Pattern:**
```php
// ✅ CORRECT (as per user convention)
protected bool $required = false;
protected bool $visible = true;
protected bool $editable = false;

// ❌ WRONG (avoid this pattern)
protected bool $isRequired = false;
protected bool $isVisible = true;
protected bool $isEditable = false;
```

---

#### Issue #4: Incomplete Serialization Groups
**Severity:** MAJOR
**Convention Violation:** API Platform best practices

**Current Groups:**
- `input:read` - Basic read operations
- `input:write` - Basic write operations
- `audit:read` - Audit information (inherited)

**Missing Groups:**
```php
// Recommended additions:
#[Groups(['input:read', 'input:write', 'input:collection'])]
protected string $name = '';

#[Groups(['input:read', 'step:read'])]  // Add to step serialization
protected Step $step;

#[Groups(['input:read', 'input:detail'])]  // Detailed view
protected Collection $connections;
```

---

#### Issue #5: Missing Validation Constraints
**Severity:** MAJOR
**Convention Violation:** Workflow validation best practices 2025

**Current Validation:**
```php
#[Assert\NotBlank]  // Only on 'name'
protected string $name = '';
```

**Recommended Additions:**
```php
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Column(length: 255)]
#[Assert\NotBlank(message: 'Input name is required')]
#[Assert\Length(
    min: 3,
    max: 255,
    minMessage: 'Input name must be at least {{ limit }} characters',
    maxMessage: 'Input name cannot exceed {{ limit }} characters'
)]
#[Groups(['input:read', 'input:write'])]
protected string $name = '';

#[ORM\Column(length: 255, nullable: true)]
#[Assert\Length(max: 255)]
#[Assert\Regex(
    pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
    message: 'Slug must be lowercase alphanumeric with hyphens'
)]
#[Groups(['input:read', 'input:write'])]
protected ?string $slug = null;

#[ORM\Column(type: 'text', nullable: true)]
#[Assert\Length(max: 5000, maxMessage: 'Prompt cannot exceed {{ limit }} characters')]
#[Groups(['input:read', 'input:write'])]
protected ?string $prompt = null;
```

---

### 2.3 RECOMMENDED Enhancements

#### Enhancement #1: Add Workflow Input Validation Fields
**Severity:** RECOMMENDED
**Justification:** Based on 2025 workflow validation best practices research

**Research Findings:**
1. **Real-time validation at data entry** - Apply validation rules at input point
2. **Schema validation** - Ensure data structures match expected patterns
3. **Input/Output validation** - Validate both incoming and outgoing data
4. **Automation** - Automated validation tools with predefined rules
5. **Human training** - Human oversight remains crucial

**Recommended New Fields:**

```php
/**
 * Whether this input is required for step execution
 */
#[ORM\Column(type: 'boolean')]
#[Groups(['input:read', 'input:write'])]
protected bool $required = false;

/**
 * Whether this input is visible in UI
 */
#[ORM\Column(type: 'boolean')]
#[Groups(['input:read', 'input:write'])]
protected bool $visible = true;

/**
 * Order of input display
 */
#[ORM\Column(type: 'integer')]
#[Groups(['input:read', 'input:write'])]
protected int $displayOrder = 1;

/**
 * Input validation rules (JSON schema)
 * Example: {"type": "string", "minLength": 3, "maxLength": 100}
 */
#[ORM\Column(type: 'json', nullable: true)]
#[Groups(['input:read', 'input:write'])]
protected ?array $validationRules = null;

/**
 * Default value for this input
 */
#[ORM\Column(type: 'text', nullable: true)]
#[Groups(['input:read', 'input:write'])]
protected ?string $defaultValue = null;

/**
 * Help text for users
 */
#[ORM\Column(type: 'text', nullable: true)]
#[Groups(['input:read', 'input:write'])]
protected ?string $helpText = null;

/**
 * Placeholder text for input field
 */
#[ORM\Column(length: 255, nullable: true)]
#[Groups(['input:read', 'input:write'])]
protected ?string $placeholder = null;

/**
 * Input data type (text, number, email, url, etc.)
 */
#[ORM\Column(length: 50)]
#[Assert\Choice(choices: ['text', 'number', 'email', 'url', 'date', 'datetime', 'boolean', 'select', 'multiselect'])]
#[Groups(['input:read', 'input:write'])]
protected string $inputDataType = 'text';

/**
 * Minimum attempts required before NOT_COMPLETED_AFTER_ATTEMPTS triggers
 */
#[ORM\Column(type: 'integer', nullable: true)]
#[Assert\Range(min: 1, max: 100)]
#[Groups(['input:read', 'input:write'])]
protected ?int $minAttempts = null;
```

**Getters/Setters:**
```php
public function isRequired(): bool
{
    return $this->required;
}

public function setRequired(bool $required): self
{
    $this->required = $required;
    return $this;
}

public function isVisible(): bool
{
    return $this->visible;
}

public function setVisible(bool $visible): self
{
    $this->visible = $visible;
    return $this;
}

// ... (add remaining getters/setters)
```

---

#### Enhancement #2: Add Custom Validation Methods
**Severity:** RECOMMENDED

```php
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Custom validation for input configuration
 */
#[Assert\Callback]
public function validate(ExecutionContextInterface $context): void
{
    // Validate minAttempts only when type is NOT_COMPLETED_AFTER_ATTEMPTS
    if ($this->type === InputType::NOT_COMPLETED_AFTER_ATTEMPTS && $this->minAttempts === null) {
        $context->buildViolation('Min attempts is required for NOT_COMPLETED_AFTER_ATTEMPTS type')
            ->atPath('minAttempts')
            ->addViolation();
    }

    // Validate slug is unique per step
    if ($this->slug !== null && $this->step !== null) {
        foreach ($this->step->getInputs() as $input) {
            if ($input !== $this && $input->getSlug() === $this->slug) {
                $context->buildViolation('Slug must be unique within the step')
                    ->atPath('slug')
                    ->addViolation();
            }
        }
    }

    // Validate validation rules JSON schema if present
    if ($this->validationRules !== null) {
        // Add JSON schema validation logic
    }
}
```

---

#### Enhancement #3: Add Business Logic Methods
**Severity:** RECOMMENDED

```php
/**
 * Check if this input is triggered by the given step completion status
 */
public function isTriggeredBy(string $completionStatus, int $attemptCount = 0): bool
{
    return match($this->type) {
        InputType::FULLY_COMPLETED => $completionStatus === 'completed',
        InputType::NOT_COMPLETED_AFTER_ATTEMPTS =>
            $completionStatus !== 'completed' &&
            $this->minAttempts !== null &&
            $attemptCount >= $this->minAttempts,
        InputType::ANY => true,
    };
}

/**
 * Get input source steps (steps that have outputs connecting to this input)
 */
public function getSourceSteps(): array
{
    $sourceSteps = [];
    foreach ($this->connections as $connection) {
        $sourceOutput = $connection->getSourceOutput();
        $sourceStep = $sourceOutput->getStep();
        if (!in_array($sourceStep, $sourceSteps, true)) {
            $sourceSteps[] = $sourceStep;
        }
    }
    return $sourceSteps;
}

/**
 * Validate input data against validation rules
 */
public function validateInputData(mixed $data): array
{
    $errors = [];

    if ($this->required && empty($data)) {
        $errors[] = 'This input is required';
    }

    if ($this->validationRules !== null) {
        // Implement JSON schema validation
        // This would integrate with a JSON schema validator library
    }

    return $errors;
}

/**
 * Check if input has any source connections
 */
public function hasSourceConnections(): bool
{
    return !$this->connections->isEmpty();
}

/**
 * Get count of incoming connections
 */
public function getConnectionCount(): int
{
    return $this->connections->count();
}
```

---

## 3. Repository Analysis

**File:** `/home/user/inf/app/src/Repository/StepInputRepository.php`

### Current Methods

```php
findByStep(Step $step): array          // ✅ Good
findBySource(Step $sourceStep): array  // ⚠️ BROKEN - references non-existent 'sourceStep' field
findByStepAndType(Step $step, InputType $type): array  // ✅ Good
```

### Issue: Broken Query in findBySource

**Problem:**
```php
public function findBySource(Step $sourceStep): array
{
    return $this->createQueryBuilder('i')
        ->where('i.sourceStep = :source')  // ⚠️ NO 'sourceStep' field exists!
        ->setParameter('source', $sourceStep)
        ->getQuery()
        ->getResult();
}
```

**Fix Required:**
```php
/**
 * Find inputs that have connections from outputs of the given source step
 */
public function findBySource(Step $sourceStep): array
{
    return $this->createQueryBuilder('i')
        ->innerJoin('i.connections', 'c')
        ->innerJoin('c.sourceOutput', 'o')
        ->innerJoin('o.step', 's')
        ->where('s = :source')
        ->setParameter('source', $sourceStep)
        ->getQuery()
        ->getResult();
}
```

### Recommended Additional Methods

```php
/**
 * Find required inputs for a step
 */
public function findRequiredByStep(Step $step): array
{
    return $this->createQueryBuilder('i')
        ->where('i.step = :step')
        ->andWhere('i.required = :required')
        ->setParameter('step', $step)
        ->setParameter('required', true)
        ->orderBy('i.displayOrder', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Find inputs by type that can be triggered by completion status
 */
public function findTriggerable(Step $step, string $completionStatus, int $attemptCount): array
{
    $qb = $this->createQueryBuilder('i')
        ->where('i.step = :step')
        ->setParameter('step', $step);

    if ($completionStatus === 'completed') {
        $qb->andWhere('i.type IN (:types)')
           ->setParameter('types', [InputType::FULLY_COMPLETED, InputType::ANY]);
    } else {
        $qb->andWhere('i.type IN (:types)')
           ->setParameter('types', [InputType::NOT_COMPLETED_AFTER_ATTEMPTS, InputType::ANY])
           ->andWhere('i.minAttempts <= :attempts OR i.type = :any')
           ->setParameter('attempts', $attemptCount)
           ->setParameter('any', InputType::ANY);
    }

    return $qb->getQuery()->getResult();
}

/**
 * Find inputs without any connections (orphaned inputs)
 */
public function findOrphaned(): array
{
    return $this->createQueryBuilder('i')
        ->leftJoin('i.connections', 'c')
        ->where('c.id IS NULL')
        ->getQuery()
        ->getResult();
}

/**
 * Find inputs by organization
 */
public function findByOrganization(Organization $organization): array
{
    return $this->createQueryBuilder('i')
        ->where('i.organization = :organization')
        ->setParameter('organization', $organization)
        ->orderBy('i.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

---

## 4. Comparison with Related Entities

### StepOutput Entity (Similar Structure)

```php
// StepOutput.php - ALSO MISSING ApiResource!
#[ORM\Entity(repositoryClass: StepOutputRepository::class)]
class StepOutput extends EntityBase
{
    protected Step $step;
    protected string $name = '';
    protected ?string $slug = null;
    protected ?string $description = null;
    protected ?string $conditional = null;
    protected ?StepConnection $connection = null;
}
```

**Note:** StepOutput has similar issues - no ApiResource, no organization field

### TreeFlow Entity (Good Example)

```php
// TreeFlow.php - GOOD EXAMPLE
#[ApiResource(
    routePrefix: '/treeflows',
    normalizationContext: ['groups' => ['treeflow:read']],
    denormalizationContext: ['groups' => ['treeflow:write']],
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        // ... more operations
    ]
)]
#[ORM\Entity(repositoryClass: TreeFlowRepository::class)]
class TreeFlow extends EntityBase
{
    // Has organization field ✅
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['treeflow:read'])]
    protected Organization $organization;
}
```

---

## 5. Recommended Migration

### Migration to Add Missing Fields

**File:** `app/migrations/Version{TIMESTAMP}.php`

```php
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * StepInput Enhancement: Add workflow validation fields
 */
final class Version{TIMESTAMP} extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add workflow validation fields to step_input table';
    }

    public function up(Schema $schema): void
    {
        // Add validation and UI control fields
        $this->addSql('ALTER TABLE step_input ADD required BOOLEAN DEFAULT false NOT NULL');
        $this->addSql('ALTER TABLE step_input ADD visible BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE step_input ADD display_order INTEGER DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE step_input ADD validation_rules JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE step_input ADD default_value TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE step_input ADD help_text TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE step_input ADD placeholder VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE step_input ADD input_data_type VARCHAR(50) DEFAULT \'text\' NOT NULL');
        $this->addSql('ALTER TABLE step_input ADD min_attempts INTEGER DEFAULT NULL');

        // Add comments for documentation
        $this->addSql('COMMENT ON COLUMN step_input.required IS \'Whether this input is required for step execution\'');
        $this->addSql('COMMENT ON COLUMN step_input.visible IS \'Whether this input is visible in UI\'');
        $this->addSql('COMMENT ON COLUMN step_input.display_order IS \'Order of input display in UI\'');
        $this->addSql('COMMENT ON COLUMN step_input.validation_rules IS \'JSON schema validation rules\'');
        $this->addSql('COMMENT ON COLUMN step_input.default_value IS \'Default value for this input\'');
        $this->addSql('COMMENT ON COLUMN step_input.help_text IS \'Help text displayed to users\'');
        $this->addSql('COMMENT ON COLUMN step_input.placeholder IS \'Placeholder text for input field\'');
        $this->addSql('COMMENT ON COLUMN step_input.input_data_type IS \'Input data type: text, number, email, url, date, datetime, boolean, select, multiselect\'');
        $this->addSql('COMMENT ON COLUMN step_input.min_attempts IS \'Minimum attempts required before NOT_COMPLETED_AFTER_ATTEMPTS triggers\'');

        // Add index for common queries
        $this->addSql('CREATE INDEX idx_step_input_required ON step_input (required)');
        $this->addSql('CREATE INDEX idx_step_input_visible ON step_input (visible)');
        $this->addSql('CREATE INDEX idx_step_input_display_order ON step_input (display_order)');

        // Add constraint for min_attempts
        $this->addSql('ALTER TABLE step_input ADD CONSTRAINT chk_min_attempts_positive CHECK (min_attempts IS NULL OR min_attempts > 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_step_input_display_order');
        $this->addSql('DROP INDEX idx_step_input_visible');
        $this->addSql('DROP INDEX idx_step_input_required');

        $this->addSql('ALTER TABLE step_input DROP CONSTRAINT chk_min_attempts_positive');

        $this->addSql('ALTER TABLE step_input DROP required');
        $this->addSql('ALTER TABLE step_input DROP visible');
        $this->addSql('ALTER TABLE step_input DROP display_order');
        $this->addSql('ALTER TABLE step_input DROP validation_rules');
        $this->addSql('ALTER TABLE step_input DROP default_value');
        $this->addSql('ALTER TABLE step_input DROP help_text');
        $this->addSql('ALTER TABLE step_input DROP placeholder');
        $this->addSql('ALTER TABLE step_input DROP input_data_type');
        $this->addSql('ALTER TABLE step_input DROP min_attempts');
    }
}
```

---

## 6. Complete Fixed Entity

**File:** `/home/user/inf/app/src/Entity/StepInput.php` (COMPLETE VERSION)

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\InputType;
use App\Repository\StepInputRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * StepInput - Defines how a Step can be entered
 *
 * Inputs define entry conditions from previous steps:
 * - Source step that can route to this step (via StepConnections)
 * - Type of completion required (fully completed, failed, or any)
 * - Additional prompt context when entering via this input
 * - Validation rules and UI configuration
 */
#[ORM\Entity(repositoryClass: StepInputRepository::class)]
#[ApiResource(
    routePrefix: '/treeflows/inputs',
    normalizationContext: ['groups' => ['input:read']],
    denormalizationContext: ['groups' => ['input:write']],
    operations: [
        new Get(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['input:read', 'input:detail', 'audit:read']]
        ),
        new GetCollection(
            uriTemplate: '',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['input:read', 'input:collection']]
        ),
        new Post(
            uriTemplate: '',
            security: "is_granted('ROLE_ADMIN')",
            validationContext: ['groups' => ['Default', 'input:create']]
        ),
        new Put(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_ADMIN')",
            validationContext: ['groups' => ['Default', 'input:update']]
        ),
        new Delete(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_ADMIN')"
        )
    ]
)]
class StepInput extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['input:read'])]
    protected Organization $organization;

    #[ORM\ManyToOne(targetEntity: Step::class, inversedBy: 'inputs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['input:read', 'input:write'])]
    protected Step $step;

    #[ORM\Column(type: 'string', enumType: InputType::class)]
    #[Groups(['input:read', 'input:write', 'input:collection'])]
    protected InputType $type = InputType::ANY;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Input name is required')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Input name must be at least {{ limit }} characters',
        maxMessage: 'Input name cannot exceed {{ limit }} characters'
    )]
    #[Groups(['input:read', 'input:write', 'input:collection', 'step:read'])]
    protected string $name = '';

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        message: 'Slug must be lowercase alphanumeric with hyphens'
    )]
    #[Groups(['input:read', 'input:write', 'input:collection'])]
    protected ?string $slug = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 5000, maxMessage: 'Prompt cannot exceed {{ limit }} characters')]
    #[Groups(['input:read', 'input:write'])]
    protected ?string $prompt = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['input:read', 'input:write', 'input:collection'])]
    protected bool $required = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['input:read', 'input:write'])]
    protected bool $visible = true;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 1000)]
    #[Groups(['input:read', 'input:write'])]
    protected int $displayOrder = 1;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['input:read', 'input:write'])]
    protected ?array $validationRules = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['input:read', 'input:write'])]
    protected ?string $defaultValue = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 1000)]
    #[Groups(['input:read', 'input:write'])]
    protected ?string $helpText = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Groups(['input:read', 'input:write'])]
    protected ?string $placeholder = null;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(choices: ['text', 'number', 'email', 'url', 'date', 'datetime', 'boolean', 'select', 'multiselect'])]
    #[Groups(['input:read', 'input:write'])]
    protected string $inputDataType = 'text';

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 1, max: 100)]
    #[Groups(['input:read', 'input:write'])]
    protected ?int $minAttempts = null;

    #[ORM\OneToMany(mappedBy: 'targetInput', targetEntity: StepConnection::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['input:read', 'input:detail'])]
    protected Collection $connections;

    public function __construct()
    {
        parent::__construct();
        $this->connections = new ArrayCollection();
    }

    // ========== Organization ==========

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    // ========== Step ==========

    public function getStep(): Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): self
    {
        $this->step = $step;
        return $this;
    }

    // ========== Type ==========

    public function getType(): InputType
    {
        return $this->type;
    }

    public function setType(InputType $type): self
    {
        $this->type = $type;
        return $this;
    }

    // ========== Name ==========

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    // ========== Slug ==========

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    // ========== Prompt ==========

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    // ========== Required ==========

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    // ========== Visible ==========

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): self
    {
        $this->visible = $visible;
        return $this;
    }

    // ========== Display Order ==========

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    // ========== Validation Rules ==========

    public function getValidationRules(): ?array
    {
        return $this->validationRules;
    }

    public function setValidationRules(?array $validationRules): self
    {
        $this->validationRules = $validationRules;
        return $this;
    }

    // ========== Default Value ==========

    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(?string $defaultValue): self
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    // ========== Help Text ==========

    public function getHelpText(): ?string
    {
        return $this->helpText;
    }

    public function setHelpText(?string $helpText): self
    {
        $this->helpText = $helpText;
        return $this;
    }

    // ========== Placeholder ==========

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(?string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    // ========== Input Data Type ==========

    public function getInputDataType(): string
    {
        return $this->inputDataType;
    }

    public function setInputDataType(string $inputDataType): self
    {
        $this->inputDataType = $inputDataType;
        return $this;
    }

    // ========== Min Attempts ==========

    public function getMinAttempts(): ?int
    {
        return $this->minAttempts;
    }

    public function setMinAttempts(?int $minAttempts): self
    {
        $this->minAttempts = $minAttempts;
        return $this;
    }

    // ========== Connections ==========

    /**
     * @return Collection<int, StepConnection>
     */
    public function getConnections(): Collection
    {
        return $this->connections;
    }

    public function addConnection(StepConnection $connection): self
    {
        if (!$this->connections->contains($connection)) {
            $this->connections->add($connection);
            $connection->setTargetInput($this);
        }
        return $this;
    }

    public function removeConnection(StepConnection $connection): self
    {
        if ($this->connections->removeElement($connection)) {
            if ($connection->getTargetInput() === $this) {
                $connection->setTargetInput(null);
            }
        }
        return $this;
    }

    // ========== Business Logic Methods ==========

    /**
     * Check if this input requires full completion
     */
    public function requiresFullCompletion(): bool
    {
        return $this->type === InputType::FULLY_COMPLETED;
    }

    /**
     * Check if this input accepts any status
     */
    public function acceptsAnyStatus(): bool
    {
        return $this->type === InputType::ANY;
    }

    /**
     * Check if this input has any connections
     */
    public function hasConnections(): bool
    {
        return !$this->connections->isEmpty();
    }

    /**
     * Check if this input is triggered by the given step completion status
     */
    public function isTriggeredBy(string $completionStatus, int $attemptCount = 0): bool
    {
        return match($this->type) {
            InputType::FULLY_COMPLETED => $completionStatus === 'completed',
            InputType::NOT_COMPLETED_AFTER_ATTEMPTS =>
                $completionStatus !== 'completed' &&
                $this->minAttempts !== null &&
                $attemptCount >= $this->minAttempts,
            InputType::ANY => true,
        };
    }

    /**
     * Get input source steps (steps that have outputs connecting to this input)
     */
    public function getSourceSteps(): array
    {
        $sourceSteps = [];
        foreach ($this->connections as $connection) {
            $sourceOutput = $connection->getSourceOutput();
            $sourceStep = $sourceOutput->getStep();
            if (!in_array($sourceStep, $sourceSteps, true)) {
                $sourceSteps[] = $sourceStep;
            }
        }
        return $sourceSteps;
    }

    /**
     * Validate input data against validation rules
     */
    public function validateInputData(mixed $data): array
    {
        $errors = [];

        if ($this->required && empty($data)) {
            $errors[] = 'This input is required';
        }

        if ($this->validationRules !== null) {
            // Implement JSON schema validation
            // This would integrate with a JSON schema validator library
            // Example: justinrainbow/json-schema
        }

        return $errors;
    }

    /**
     * Check if input has any source connections
     */
    public function hasSourceConnections(): bool
    {
        return !$this->connections->isEmpty();
    }

    /**
     * Get count of incoming connections
     */
    public function getConnectionCount(): int
    {
        return $this->connections->count();
    }

    // ========== Validation ==========

    /**
     * Custom validation for input configuration
     */
    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // Validate minAttempts only when type is NOT_COMPLETED_AFTER_ATTEMPTS
        if ($this->type === InputType::NOT_COMPLETED_AFTER_ATTEMPTS && $this->minAttempts === null) {
            $context->buildViolation('Min attempts is required for NOT_COMPLETED_AFTER_ATTEMPTS type')
                ->atPath('minAttempts')
                ->addViolation();
        }

        // Validate slug is unique per step
        if ($this->slug !== null && $this->step !== null) {
            foreach ($this->step->getInputs() as $input) {
                if ($input !== $this && $input->getSlug() === $this->slug) {
                    $context->buildViolation('Slug must be unique within the step')
                        ->atPath('slug')
                        ->addViolation();
                }
            }
        }

        // Validate inputDataType compatibility
        if ($this->inputDataType === 'number' && $this->defaultValue !== null && !is_numeric($this->defaultValue)) {
            $context->buildViolation('Default value must be numeric for number input type')
                ->atPath('defaultValue')
                ->addViolation();
        }
    }

    // ========== String Representation ==========

    public function __toString(): string
    {
        return $this->name . ' [' . $this->type->value . ']';
    }
}
```

---

## 7. Action Items Summary

### CRITICAL (Must Fix Immediately)

- [ ] **Add ApiResource annotation** to StepInput entity
- [ ] **Add Organization field mapping** to StepInput entity
- [ ] **Fix broken findBySource() method** in StepInputRepository

### MAJOR (Should Fix Soon)

- [ ] **Expand validation constraints** (NotBlank, Length, Regex)
- [ ] **Enhance serialization groups** (collection, detail views)
- [ ] **Add missing getter/setter** for organization field

### RECOMMENDED (Consider for Production)

- [ ] **Add workflow validation fields** (required, visible, displayOrder, etc.)
- [ ] **Create migration** for new fields
- [ ] **Add custom validation method** (@Callback)
- [ ] **Add business logic methods** (isTriggeredBy, getSourceSteps, etc.)
- [ ] **Add repository methods** (findRequiredByStep, findTriggerable, etc.)
- [ ] **Update form** to include new fields
- [ ] **Update templates** to display new fields
- [ ] **Add API documentation** for new endpoints

---

## 8. Testing Recommendations

### Unit Tests

```php
// tests/Entity/StepInputTest.php
class StepInputTest extends KernelTestCase
{
    public function testInputCreation(): void
    {
        $input = new StepInput();
        $input->setName('Test Input');
        $input->setType(InputType::FULLY_COMPLETED);

        $this->assertEquals('Test Input', $input->getName());
        $this->assertTrue($input->requiresFullCompletion());
    }

    public function testIsTriggeredBy(): void
    {
        $input = new StepInput();
        $input->setType(InputType::FULLY_COMPLETED);

        $this->assertTrue($input->isTriggeredBy('completed'));
        $this->assertFalse($input->isTriggeredBy('failed'));
    }

    public function testMinAttemptsValidation(): void
    {
        $input = new StepInput();
        $input->setType(InputType::NOT_COMPLETED_AFTER_ATTEMPTS);
        $input->setMinAttempts(null); // Should fail validation

        $validator = static::getContainer()->get('validator');
        $errors = $validator->validate($input);

        $this->assertCount(1, $errors);
    }
}
```

### API Tests

```php
// tests/Api/StepInputTest.php
class StepInputApiTest extends ApiTestCase
{
    public function testGetInputCollection(): void
    {
        $client = static::createClient();
        $response = $client->request('GET', '/api/treeflows/inputs');

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['@context' => '/api/contexts/StepInput']);
    }

    public function testCreateInput(): void
    {
        $client = static::createClient();
        $response = $client->request('POST', '/api/treeflows/inputs', [
            'json' => [
                'name' => 'New Input',
                'type' => 'any',
                'step' => '/api/steps/123',
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
    }
}
```

---

## 9. Performance Considerations

### Database Indexes

```sql
-- Existing indexes
CREATE INDEX idx_step_input_step ON step_input (step_id);
CREATE INDEX idx_step_input_organization ON step_input (organization_id);

-- Recommended additional indexes
CREATE INDEX idx_step_input_type ON step_input (type);
CREATE INDEX idx_step_input_required ON step_input (required);
CREATE INDEX idx_step_input_visible ON step_input (visible);
CREATE INDEX idx_step_input_display_order ON step_input (display_order);
CREATE INDEX idx_step_input_slug ON step_input (slug);

-- Composite index for common queries
CREATE INDEX idx_step_input_step_type ON step_input (step_id, type);
CREATE INDEX idx_step_input_step_order ON step_input (step_id, display_order);
```

### Query Optimization

1. **Eager Loading:** Use JOIN FETCH when loading inputs with connections
2. **Caching:** Consider caching frequently accessed inputs
3. **Pagination:** Implement pagination for large input collections
4. **Projection:** Use partial selects when full entity not needed

---

## 10. Security Considerations

### Access Control

```php
// Implement custom voter for StepInput
class StepInputVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof StepInput && in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var StepInput $input */
        $input = $subject;

        // Check organization membership
        if ($input->getOrganization() !== $user->getOrganization()) {
            return false;
        }

        return match($attribute) {
            self::VIEW => $user->hasRole('ROLE_USER'),
            self::EDIT, self::DELETE => $user->hasRole('ROLE_ADMIN'),
            default => false,
        };
    }
}
```

---

## Conclusion

The StepInput entity requires **CRITICAL fixes** before production use:

1. **Add ApiResource** annotation for REST API exposure
2. **Map organization field** for multi-tenant isolation
3. **Fix repository queries** that reference non-existent fields

After critical fixes, consider **RECOMMENDED enhancements** for:
- Comprehensive validation rules
- Rich UI configuration options
- Better workflow automation support

**Total Estimated Effort:**
- Critical fixes: 2-3 hours
- Major improvements: 4-6 hours
- Recommended enhancements: 8-12 hours
- Testing: 4-6 hours

**Priority:** HIGH - Address critical issues immediately to maintain API consistency and multi-tenant security.
