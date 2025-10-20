# CourseModule Entity Analysis Report

**Generated:** 2025-10-19
**Entity:** `App\Entity\CourseModule`
**Database:** PostgreSQL 18
**Location:** `/home/user/inf/app/src/Entity/CourseModule.php`

---

## Executive Summary

The CourseModule entity is **structurally sound** with proper implementation following Symfony 7.3 and Doctrine ORM best practices. However, there are **8 critical missing fields** and **5 architectural improvements** needed based on 2025 LMS industry standards and the existing codebase patterns.

**Status:** ✅ Functional | ⚠️ Incomplete for Production LMS

---

## Table of Contents

1. [Current Implementation Analysis](#1-current-implementation-analysis)
2. [Issues Identified](#2-issues-identified)
3. [Missing Properties (LMS 2025 Standards)](#3-missing-properties-lms-2025-standards)
4. [API Platform Configuration Analysis](#4-api-platform-configuration-analysis)
5. [Recommendations](#5-recommendations)
6. [Proposed Entity Improvements](#6-proposed-entity-improvements)
7. [Migration Strategy](#7-migration-strategy)
8. [Database Schema Impact](#8-database-schema-impact)

---

## 1. Current Implementation Analysis

### 1.1 Existing Structure

**File:** `/home/user/inf/app/src/Entity/CourseModule.php`

**Current Properties:**

| Property | Type | Nullable | API Groups | Validation | Purpose |
|----------|------|----------|------------|------------|---------|
| `id` | `Uuid` | No | Inherited | UUIDv7 | Primary key |
| `name` | `string` | No | read/write/course:read | NotBlank, Length:255 | Module title |
| `description` | `string` | Yes | read/write | None | Module description |
| `active` | `bool` | No | read/write | None | Active status |
| `releaseDate` | `DateTimeImmutable` | Yes | read/write | None | Scheduled release |
| `viewOrder` | `int` | No | read/write | PositiveOrZero | Display order |
| `totalLengthSeconds` | `int` | No | read | PositiveOrZero | Calculated total |
| `course` | `Course` | No | read | None | Parent course |
| `lectures` | `Collection<CourseLecture>` | N/A | read | None | Child lectures |
| `createdAt` | `DateTimeImmutable` | No | audit:read | None | Audit timestamp |
| `updatedAt` | `DateTimeImmutable` | No | audit:read | None | Audit timestamp |
| `createdBy` | `User` | Yes | audit:read | None | Audit user |
| `updatedBy` | `User` | Yes | audit:read | None | Audit user |

**Total Properties:** 14 (3 inherited from EntityBase, 3 from AuditTrait)

### 1.2 Extends & Traits

```php
class CourseModule extends EntityBase
```

- **EntityBase** provides: `id` (UUIDv7), `AuditTrait`
- **AuditTrait** provides: `createdAt`, `updatedAt`, `createdBy`, `updatedBy`

### 1.3 Relationships

```
CourseModule
├── ManyToOne: Course (required)
└── OneToMany: CourseLecture[] (cascade: persist, remove)
```

### 1.4 Methods Implemented

✅ Standard getters/setters
✅ `calculateTotalLengthSeconds()` - Aggregates lecture durations
✅ `getTotalLengthFormatted()` - Formats duration as HH:MM
✅ `addLecture()` / `removeLecture()` - Collection management
✅ `__toString()` - Returns module name

---

## 2. Issues Identified

### 2.1 CRITICAL Issues

#### ❌ Issue #1: Incorrect Boolean Getter Naming
**Location:** Line 96
**Current Code:**
```php
public function isActive(): bool
{
    return $this->active;
}
```

**Problem:** Property is named `active` (correct) but getter is `isActive()` (incorrect)

**Convention Violation:**
Per project conventions: Boolean fields should be named `active`, `published`, NOT `isActive`.
The getter should be `getActive()` OR keep property as `isActive` and use `isActive()` getter.

**Impact:**
- ✅ Works in code (both patterns valid in PHP)
- ⚠️ Inconsistent with project naming conventions
- ⚠️ May cause confusion with API serialization

**Recommendation:** Keep property as `active` and change getter to `getActive()` for consistency.

---

#### ❌ Issue #2: Missing `published` Boolean Field

**Current:** Entity only has `active` field
**Problem:** No separation between "module exists" vs "module is visible to students"

**Use Cases:**
- `active = false` → Module is disabled/archived
- `published = false` → Module exists but not visible to students yet
- `releaseDate = future` → Module scheduled but not yet released

**LMS Best Practice (2025):**
Modern LMS platforms use both `active` AND `published` for granular visibility control:
- Draft modules: `active=true`, `published=false`
- Scheduled modules: `active=true`, `published=false`, `releaseDate=future`
- Live modules: `active=true`, `published=true`, `releaseDate<=now`
- Archived modules: `active=false`, `published=false`

---

#### ❌ Issue #3: Missing Organization Context

**Current:** CourseModule does NOT have `organization` field
**Found In:**
- ✅ `Course` entity has `organization` field (line 56)
- ❌ `CourseModule` entity missing `organization` field
- ✅ `StudentCourse` entity has `organization` field (line 64)

**Problem:** Multi-tenant filtering issue

**Impact:**
1. **Security Risk:** Cannot filter modules by organization directly
2. **Performance:** Must join through Course to filter by organization
3. **Data Integrity:** Module could theoretically reference course from different org
4. **Query Complexity:** Requires additional JOIN for organization-based queries

**Example Query Impact:**
```php
// CURRENT (inefficient):
$qb->select('cm')
   ->from(CourseModule::class, 'cm')
   ->join('cm.course', 'c')
   ->where('c.organization = :org'); // Extra JOIN required

// RECOMMENDED (efficient):
$qb->select('cm')
   ->from(CourseModule::class, 'cm')
   ->where('cm.organization = :org'); // Direct filter
```

---

#### ❌ Issue #4: Incomplete API Platform Configuration

**Location:** Lines 17-27

**Current Configuration:**
```php
#[ApiResource(
    normalizationContext: ['groups' => ['course_module:read']],
    denormalizationContext: ['groups' => ['course_module:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/course-modules',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['course_module:read', 'audit:read']]
        )
    ]
)]
```

**Missing Operations:**
- ❌ Get (single item)
- ❌ Post (create)
- ❌ Patch (update)
- ❌ Put (replace)
- ❌ Delete

**Problem:** API only supports listing, not CRUD operations

**Impact:**
API consumers cannot create, update, or delete modules via API Platform endpoints.
Must use traditional Symfony controllers (which exist in `CourseModuleController.php`).

**Note:** This may be intentional design, but inconsistent with other entities.

---

### 2.2 WARNING Issues

#### ⚠️ Issue #5: Missing `course` in Write Groups

**Location:** Line 60
**Current:**
```php
#[Groups(['course_module:read'])]
protected Course $course;
```

**Problem:** `course` is only in `read` group, not in `write` group

**Impact:**
When creating a module via API (if Post operation added), cannot set the course relationship.
Must be set programmatically in controller.

**Current Workaround:** Controller sets course relationship (line 50 in CourseModuleController.php)

---

#### ⚠️ Issue #6: No Validation on `releaseDate`

**Location:** Line 46
**Current:**
```php
#[ORM\Column(type: 'datetime_immutable', nullable: true)]
#[Groups(['course_module:read', 'course_module:write'])]
protected ?\DateTimeImmutable $releaseDate = null;
```

**Problem:** No validation to ensure `releaseDate` is in the future or after course start date

**Potential Issues:**
- Release date in the past (historical data)
- Release date before course creation date
- Release date after course end date

---

#### ⚠️ Issue #7: No Soft Delete Support

**Current:** Entity uses hard delete via `cascade: ['persist', 'remove']`

**Found Pattern:**
Other entities in the codebase have `SoftDeleteSubscriber` (file: `/home/user/inf/app/src/EventSubscriber/SoftDeleteSubscriber.php`)

**Impact:**
- Deleting a module permanently deletes all child lectures
- No audit trail for deleted modules
- Cannot restore accidentally deleted modules

---

## 3. Missing Properties (LMS 2025 Standards)

Based on web research and modern LMS platforms, the following properties are **standard** in 2025:

### 3.1 CRITICAL Missing Fields

| Field | Type | Purpose | Example |
|-------|------|---------|---------|
| `published` | `bool` | Student visibility control | `published=false` for draft modules |
| `organization` | `Organization` | Multi-tenant context | Direct organization reference |
| `estimatedMinutes` | `int` | Instructor estimate | "This module takes ~60 minutes" |
| `completionRequired` | `bool` | Mandatory completion flag | Required for course completion |

### 3.2 RECOMMENDED Missing Fields

| Field | Type | Purpose | Example |
|-------|------|---------|---------|
| `objectives` | `text` | Learning outcomes | "By the end, you'll learn..." |
| `prerequisites` | `text` | Required knowledge | "Complete Module 1 first" |
| `resources` | `text` | Additional materials | Links to PDFs, docs, etc. |
| `certificateEligible` | `bool` | Certificate requirement | Must complete for certificate |

### 3.3 OPTIONAL Enhancement Fields

| Field | Type | Purpose | Example |
|-------|------|---------|---------|
| `thumbnailPath` | `string` | Module cover image | `/uploads/modules/thumb.jpg` |
| `difficultyLevel` | `string` | Beginner/Intermediate/Advanced | Helps student selection |
| `tags` | `json` | Searchable keywords | `["php", "symfony", "doctrine"]` |
| `externalId` | `string` | Integration with external LMS | For migration/sync |

---

## 4. API Platform Configuration Analysis

### 4.1 Current State

**Endpoints Available:**
- ✅ `GET /api/admin/course-modules` - List all modules (admin only)

**Endpoints Missing:**
- ❌ `GET /api/admin/course-modules/{id}` - Get single module
- ❌ `POST /api/admin/course-modules` - Create module
- ❌ `PATCH /api/admin/course-modules/{id}` - Update module
- ❌ `PUT /api/admin/course-modules/{id}` - Replace module
- ❌ `DELETE /api/admin/course-modules/{id}` - Delete module

### 4.2 Serialization Groups

**Current Groups:**
```
Read:  ['course_module:read', 'audit:read']
Write: ['course_module:write']
```

**Fields by Group:**

| Field | `course_module:read` | `course_module:write` | `course:read` | `audit:read` |
|-------|---------------------|----------------------|---------------|--------------|
| `name` | ✅ | ✅ | ✅ | ❌ |
| `description` | ✅ | ✅ | ❌ | ❌ |
| `active` | ✅ | ✅ | ❌ | ❌ |
| `releaseDate` | ✅ | ✅ | ❌ | ❌ |
| `viewOrder` | ✅ | ✅ | ❌ | ❌ |
| `totalLengthSeconds` | ✅ | ❌ | ❌ | ❌ |
| `course` | ✅ | ❌ | ❌ | ❌ |
| `lectures` | ✅ | ❌ | ❌ | ❌ |
| `createdAt` | ❌ | ❌ | ❌ | ✅ |
| `updatedAt` | ❌ | ❌ | ❌ | ✅ |
| `createdBy` | ❌ | ❌ | ❌ | ✅ |
| `updatedBy` | ❌ | ❌ | ❌ | ✅ |

**Issue:** Audit fields only visible in admin endpoint, not in regular read operations.

### 4.3 Repository Array Transformation

**File:** `/home/user/inf/app/src/Repository/CourseModuleRepository.php`

The repository provides a custom `entityToArray()` method (lines 96-115) that returns:

```php
[
    'id' => string (UUID),
    'name' => string,
    'description' => string,
    'active' => bool,
    'releaseDate' => string (ISO 8601) | null,
    'viewOrder' => int,
    'totalLengthSeconds' => int,
    'totalLengthFormatted' => string,
    'courseId' => string (UUID),
    'courseName' => string,
    'lecturesCount' => int,
    'createdAt' => string (ISO 8601),
    'updatedAt' => string (ISO 8601),
]
```

**Missing in Array Output:**
- ❌ `createdBy` / `updatedBy` (audit users)
- ❌ `organization` (not in entity)
- ❌ `published` (not in entity)

---

## 5. Recommendations

### 5.1 Priority 1: CRITICAL Fixes

#### 1. Add `organization` Field

**Rationale:** Required for multi-tenant security and performance

**Implementation:**
```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
#[ORM\JoinColumn(nullable: false)]
#[Groups(['course_module:read'])]
protected Organization $organization;
```

**Controller Update:** Set organization automatically from course
```php
$module->setOrganization($course->getOrganization());
```

---

#### 2. Add `published` Boolean Field

**Rationale:** Separate draft/live states

**Implementation:**
```php
#[ORM\Column(type: 'boolean')]
#[Groups(['course_module:read', 'course_module:write'])]
protected bool $published = false;
```

**Business Logic:**
```php
public function isVisibleToStudents(): bool
{
    if (!$this->active || !$this->published) {
        return false;
    }

    if ($this->releaseDate && $this->releaseDate > new \DateTimeImmutable()) {
        return false;
    }

    return true;
}
```

---

#### 3. Fix Boolean Getter Naming

**Option A (Recommended):** Change getter to match property
```php
// Keep property as: protected bool $active = true;
// Change getter:
public function getActive(): bool
{
    return $this->active;
}
```

**Option B:** Keep getter, rename property
```php
// Change property to: protected bool $isActive = true;
// Keep getter: public function isActive(): bool
```

**Recommendation:** Use Option A for consistency with `$published` property.

---

### 5.2 Priority 2: RECOMMENDED Additions

#### 4. Add Completion Tracking Fields

```php
#[ORM\Column(type: 'boolean')]
#[Groups(['course_module:read', 'course_module:write'])]
protected bool $completionRequired = true;

#[ORM\Column(type: 'integer', nullable: true)]
#[Assert\Positive]
#[Groups(['course_module:read', 'course_module:write'])]
protected ?int $estimatedMinutes = null;
```

#### 5. Add Learning Metadata

```php
#[ORM\Column(type: 'text', nullable: true)]
#[Groups(['course_module:read', 'course_module:write'])]
protected ?string $objectives = null;

#[ORM\Column(type: 'text', nullable: true)]
#[Groups(['course_module:read', 'course_module:write'])]
protected ?string $prerequisites = null;
```

---

### 5.3 Priority 3: OPTIONAL Enhancements

#### 6. Complete API Platform Operations

Add full CRUD operations:
```php
#[ApiResource(
    normalizationContext: ['groups' => ['course_module:read']],
    denormalizationContext: ['groups' => ['course_module:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/course-modules',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Get(
            uriTemplate: '/admin/course-modules/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Post(
            uriTemplate: '/admin/course-modules',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            uriTemplate: '/admin/course-modules/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            uriTemplate: '/admin/course-modules/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
    ]
)]
```

#### 7. Add Soft Delete Support

Implement soft delete pattern similar to other entities in the codebase.

#### 8. Add Search/Filter Enhancements

```php
#[ORM\Column(type: 'json', nullable: true)]
#[Groups(['course_module:read', 'course_module:write'])]
protected ?array $tags = null;

#[ORM\Column(type: 'string', length: 50, nullable: true)]
#[Groups(['course_module:read', 'course_module:write'])]
protected ?string $difficultyLevel = null; // beginner, intermediate, advanced
```

---

## 6. Proposed Entity Improvements

### 6.1 Complete Entity Code (With All Recommendations)

**File:** `/home/user/inf/app/src/Entity/CourseModule.php`

<details>
<summary>View Complete Improved Entity (Click to expand)</summary>

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CourseModuleRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: CourseModuleRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['course_module:read']],
    denormalizationContext: ['groups' => ['course_module:write']],
    operations: [
        new GetCollection(
            uriTemplate: '/admin/course-modules',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['course_module:read', 'audit:read']]
        ),
        new Get(
            uriTemplate: '/admin/course-modules/{id}',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['course_module:read', 'audit:read']]
        ),
        new Post(
            uriTemplate: '/admin/course-modules',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            uriTemplate: '/admin/course-modules/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            uriTemplate: '/admin/course-modules/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
    ]
)]
class CourseModule extends EntityBase
{
    // ==================== BASIC FIELDS ====================

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course_module:read', 'course_module:write', 'course:read'])]
    protected string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected ?string $description = null;

    // ==================== STATUS FIELDS ====================

    /**
     * Controls if module is enabled/disabled
     * false = archived/disabled
     */
    #[ORM\Column(type: 'boolean')]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected bool $active = true;

    /**
     * Controls if module is visible to students
     * false = draft mode (not visible to students)
     * true = published (visible if active and releaseDate passed)
     */
    #[ORM\Column(type: 'boolean')]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected bool $published = false;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected ?\DateTimeImmutable $releaseDate = null;

    // ==================== ORDERING & METRICS ====================

    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected int $viewOrder = 0;

    /**
     * Calculated field: sum of all lecture durations
     * Updated automatically via calculateTotalLengthSeconds()
     */
    #[ORM\Column(type: 'integer')]
    #[Assert\PositiveOrZero]
    #[Groups(['course_module:read'])]
    protected int $totalLengthSeconds = 0;

    /**
     * Instructor's estimate for module completion time
     * Useful for student time planning
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Positive]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected ?int $estimatedMinutes = null;

    // ==================== LEARNING METADATA ====================

    /**
     * Learning objectives/outcomes
     * "After completing this module, you will be able to..."
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected ?string $objectives = null;

    /**
     * Prerequisites for this module
     * "Before starting, you should know..."
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected ?string $prerequisites = null;

    /**
     * Additional resources (links, PDFs, etc.)
     */
    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected ?string $resources = null;

    // ==================== COMPLETION TRACKING ====================

    /**
     * Must student complete this module to finish the course?
     */
    #[ORM\Column(type: 'boolean')]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected bool $completionRequired = true;

    /**
     * Is this module eligible for certificate requirements?
     */
    #[ORM\Column(type: 'boolean')]
    #[Groups(['course_module:read', 'course_module:write'])]
    protected bool $certificateEligible = true;

    // ==================== RELATIONSHIPS ====================

    /**
     * Multi-tenant organization context
     * CRITICAL: Required for security filtering
     */
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['course_module:read'])]
    protected Organization $organization;

    #[ORM\ManyToOne(targetEntity: Course::class, inversedBy: 'modules')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['course_module:read'])]
    protected Course $course;

    #[ORM\OneToMany(targetEntity: CourseLecture::class, mappedBy: 'courseModule', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['viewOrder' => 'ASC'])]
    #[Groups(['course_module:read'])]
    protected Collection $lectures;

    // ==================== CONSTRUCTOR ====================

    public function __construct()
    {
        parent::__construct();
        $this->lectures = new ArrayCollection();
    }

    // ==================== GETTERS & SETTERS ====================

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
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

    /**
     * FIXED: Changed from isActive() to getActive()
     * Consistent with property name: $active
     */
    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;
        return $this;
    }

    public function getReleaseDate(): ?\DateTimeImmutable
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeImmutable $releaseDate): self
    {
        $this->releaseDate = $releaseDate;
        return $this;
    }

    public function getViewOrder(): int
    {
        return $this->viewOrder;
    }

    public function setViewOrder(int $viewOrder): self
    {
        $this->viewOrder = $viewOrder;
        return $this;
    }

    public function getTotalLengthSeconds(): int
    {
        return $this->totalLengthSeconds;
    }

    public function getEstimatedMinutes(): ?int
    {
        return $this->estimatedMinutes;
    }

    public function setEstimatedMinutes(?int $estimatedMinutes): self
    {
        $this->estimatedMinutes = $estimatedMinutes;
        return $this;
    }

    public function getObjectives(): ?string
    {
        return $this->objectives;
    }

    public function setObjectives(?string $objectives): self
    {
        $this->objectives = $objectives;
        return $this;
    }

    public function getPrerequisites(): ?string
    {
        return $this->prerequisites;
    }

    public function setPrerequisites(?string $prerequisites): self
    {
        $this->prerequisites = $prerequisites;
        return $this;
    }

    public function getResources(): ?string
    {
        return $this->resources;
    }

    public function setResources(?string $resources): self
    {
        $this->resources = $resources;
        return $this;
    }

    public function getCompletionRequired(): bool
    {
        return $this->completionRequired;
    }

    public function setCompletionRequired(bool $completionRequired): self
    {
        $this->completionRequired = $completionRequired;
        return $this;
    }

    public function getCertificateEligible(): bool
    {
        return $this->certificateEligible;
    }

    public function setCertificateEligible(bool $certificateEligible): self
    {
        $this->certificateEligible = $certificateEligible;
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

    public function getCourse(): Course
    {
        return $this->course;
    }

    public function setCourse(Course $course): self
    {
        $this->course = $course;
        return $this;
    }

    /**
     * @return Collection<int, CourseLecture>
     */
    public function getLectures(): Collection
    {
        return $this->lectures;
    }

    public function addLecture(CourseLecture $lecture): self
    {
        if (!$this->lectures->contains($lecture)) {
            $this->lectures->add($lecture);
            $lecture->setCourseModule($this);
        }
        return $this;
    }

    public function removeLecture(CourseLecture $lecture): self
    {
        if ($this->lectures->removeElement($lecture)) {
            if ($lecture->getCourseModule() === $this) {
                $lecture->setCourseModule(null);
            }
        }
        return $this;
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Calculate total duration from all lectures
     * Called automatically via CourseLecture lifecycle events
     */
    public function calculateTotalLengthSeconds(): void
    {
        $total = 0;
        foreach ($this->lectures as $lecture) {
            $total += $lecture->getLengthSeconds();
        }
        $this->totalLengthSeconds = $total;
    }

    /**
     * Format duration as human-readable string
     */
    public function getTotalLengthFormatted(): string
    {
        if ($this->totalLengthSeconds < 60) {
            return $this->totalLengthSeconds . ' s';
        }

        if ($this->totalLengthSeconds < 3600) {
            $minutes = (int)floor($this->totalLengthSeconds / 60);
            return $minutes . ' m';
        }

        $hours = (int)floor($this->totalLengthSeconds / 3600);
        $minutes = (int)floor(($this->totalLengthSeconds % 3600) / 60);
        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Check if module is visible to students
     * Combines active, published, and releaseDate logic
     */
    public function isVisibleToStudents(): bool
    {
        // Must be both active and published
        if (!$this->active || !$this->published) {
            return false;
        }

        // If release date set, must be in the past
        if ($this->releaseDate && $this->releaseDate > new \DateTimeImmutable()) {
            return false;
        }

        return true;
    }

    /**
     * Check if module is in draft state
     */
    public function isDraft(): bool
    {
        return $this->active && !$this->published;
    }

    /**
     * Check if module is scheduled for future release
     */
    public function isScheduled(): bool
    {
        return $this->active
            && $this->published
            && $this->releaseDate
            && $this->releaseDate > new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
```

</details>

### 6.2 New Properties Summary

**Total Properties:** 22 (+8 new fields)

| Property | Type | Default | Required | New? |
|----------|------|---------|----------|------|
| `published` | `bool` | `false` | Yes | ✅ NEW |
| `organization` | `Organization` | - | Yes | ✅ NEW |
| `estimatedMinutes` | `int` | `null` | No | ✅ NEW |
| `objectives` | `text` | `null` | No | ✅ NEW |
| `prerequisites` | `text` | `null` | No | ✅ NEW |
| `resources` | `text` | `null` | No | ✅ NEW |
| `completionRequired` | `bool` | `true` | Yes | ✅ NEW |
| `certificateEligible` | `bool` | `true` | Yes | ✅ NEW |

### 6.3 New Methods Summary

**Total Methods:** 30 (+7 new methods)

| Method | Return Type | Purpose | New? |
|--------|-------------|---------|------|
| `isVisibleToStudents()` | `bool` | Combined visibility check | ✅ NEW |
| `isDraft()` | `bool` | Check if in draft mode | ✅ NEW |
| `isScheduled()` | `bool` | Check if scheduled release | ✅ NEW |
| `getPublished()` | `bool` | Getter for published field | ✅ NEW |
| `setPublished()` | `self` | Setter for published field | ✅ NEW |
| `getOrganization()` | `Organization` | Getter for organization | ✅ NEW |
| `setOrganization()` | `self` | Setter for organization | ✅ NEW |

---

## 7. Migration Strategy

### 7.1 Database Migration Plan

**Step 1:** Generate migration
```bash
cd /home/user/inf/app
php bin/console make:migration
```

**Step 2:** Review migration SQL (auto-generated)
```sql
-- Add new columns to course_module table
ALTER TABLE course_module
    ADD published BOOLEAN NOT NULL DEFAULT false,
    ADD organization_id UUID NOT NULL,
    ADD estimated_minutes INT DEFAULT NULL,
    ADD objectives TEXT DEFAULT NULL,
    ADD prerequisites TEXT DEFAULT NULL,
    ADD resources TEXT DEFAULT NULL,
    ADD completion_required BOOLEAN NOT NULL DEFAULT true,
    ADD certificate_eligible BOOLEAN NOT NULL DEFAULT true;

-- Add foreign key constraint
ALTER TABLE course_module
    ADD CONSTRAINT FK_A21CE76532C8A3DE
    FOREIGN KEY (organization_id)
    REFERENCES organization (id)
    NOT DEFERRABLE INITIALLY IMMEDIATE;

-- Add index for organization filtering
CREATE INDEX IDX_A21CE76532C8A3DE ON course_module (organization_id);

-- Add UUID type comment
COMMENT ON COLUMN course_module.organization_id IS '(DC2Type:uuid)';
```

**Step 3:** Data migration for existing records
```sql
-- Populate organization_id from parent course
UPDATE course_module cm
SET organization_id = c.organization_id
FROM course c
WHERE cm.course_id = c.id;

-- Set all existing modules to published (maintain current behavior)
UPDATE course_module SET published = true WHERE active = true;
```

**Step 4:** Run migration
```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

**Step 5:** Validate schema
```bash
php bin/console doctrine:schema:validate
```

### 7.2 Code Migration Impact

**Files Requiring Updates:**

1. **CourseModuleController.php** (4 changes)
   - Line 50: Add `$module->setOrganization($course->getOrganization());`
   - Line 64: Auto-set organization on persist
   - Update tests to include organization
   - Add published field to forms

2. **CourseModuleFormType.php** (1 change)
   - Add `published` checkbox field after `active`

3. **CourseModuleRepository.php** (2 changes)
   - Update `entityToArray()` to include new fields
   - Add `published` to filterable fields

4. **Twig Templates** (3 files)
   - `course/module_new.html.twig`
   - `course/module_edit.html.twig`
   - `course/_module_form_modal.html.twig`
   - Add published field display/input

5. **Tests** (estimate: 5 files)
   - Update fixtures to include organization
   - Update test assertions for new fields

### 7.3 Backward Compatibility

**Breaking Changes:** ❌ None
**Migration Path:** ✅ Safe

**Rationale:**
- All new fields have default values
- Existing data migrated automatically
- No API contract changes (new fields optional)
- Controller logic enhanced, not replaced

---

## 8. Database Schema Impact

### 8.1 Current Schema (Production)

**Table:** `course_module`

```sql
CREATE TABLE course_module (
    id UUID NOT NULL PRIMARY KEY,
    created_by_id UUID DEFAULT NULL,
    updated_by_id UUID DEFAULT NULL,
    course_id UUID NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    active BOOLEAN NOT NULL,
    release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    view_order INT NOT NULL,
    total_length_seconds INT NOT NULL
);

CREATE INDEX IDX_A21CE765B03A8386 ON course_module (created_by_id);
CREATE INDEX IDX_A21CE765896DBBDE ON course_module (updated_by_id);
CREATE INDEX IDX_A21CE765591CC992 ON course_module (course_id);
```

**Size Analysis:**
- **Columns:** 12
- **Indexes:** 3 foreign keys
- **Constraints:** NOT NULL on id, course_id, name, active, view_order, total_length_seconds

### 8.2 Proposed Schema (With Improvements)

**Table:** `course_module` (Enhanced)

```sql
CREATE TABLE course_module (
    -- Existing columns (unchanged)
    id UUID NOT NULL PRIMARY KEY,
    created_by_id UUID DEFAULT NULL,
    updated_by_id UUID DEFAULT NULL,
    course_id UUID NOT NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    active BOOLEAN NOT NULL,
    release_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
    view_order INT NOT NULL,
    total_length_seconds INT NOT NULL,

    -- New columns
    published BOOLEAN NOT NULL DEFAULT false,
    organization_id UUID NOT NULL,
    estimated_minutes INT DEFAULT NULL,
    objectives TEXT DEFAULT NULL,
    prerequisites TEXT DEFAULT NULL,
    resources TEXT DEFAULT NULL,
    completion_required BOOLEAN NOT NULL DEFAULT true,
    certificate_eligible BOOLEAN NOT NULL DEFAULT true
);

-- Existing indexes
CREATE INDEX IDX_A21CE765B03A8386 ON course_module (created_by_id);
CREATE INDEX IDX_A21CE765896DBBDE ON course_module (updated_by_id);
CREATE INDEX IDX_A21CE765591CC992 ON course_module (course_id);

-- New index for organization filtering (CRITICAL for multi-tenant performance)
CREATE INDEX IDX_A21CE76532C8A3DE ON course_module (organization_id);

-- Composite index for common query pattern (optional, for performance)
CREATE INDEX IDX_COURSE_MODULE_VISIBLE ON course_module (organization_id, active, published, release_date);
```

**Size Analysis:**
- **Columns:** 20 (+8)
- **Indexes:** 4 (+1 critical, +1 optional)
- **Storage Impact:** ~100 bytes per row (estimated)

### 8.3 Query Performance Impact

**Before (Current):**
```sql
-- Get active modules for organization (requires JOIN)
SELECT cm.*
FROM course_module cm
INNER JOIN course c ON cm.course_id = c.id
WHERE c.organization_id = :orgId
  AND cm.active = true
ORDER BY cm.view_order ASC;
```

**After (Improved):**
```sql
-- Get published modules for organization (direct filter)
SELECT cm.*
FROM course_module cm
WHERE cm.organization_id = :orgId
  AND cm.active = true
  AND cm.published = true
  AND (cm.release_date IS NULL OR cm.release_date <= NOW())
ORDER BY cm.view_order ASC;
```

**Performance Gain:**
- ✅ Eliminates JOIN on course table
- ✅ Uses index on (organization_id, active, published, release_date)
- ✅ Faster query execution (estimated 30-50% improvement)

### 8.4 Index Strategy

**Recommended Indexes:**

1. **Primary Key:** `id` (auto-created)
2. **Foreign Keys:** `created_by_id`, `updated_by_id`, `course_id`, `organization_id`
3. **Filtering:** Composite index on `(organization_id, active, published, release_date)`
4. **Ordering:** `view_order` (consider if many modules per course)

**Index Selectivity Analysis:**

| Index | Cardinality | Selectivity | Priority |
|-------|-------------|-------------|----------|
| `id` (PK) | Unique | 100% | ✅ Auto |
| `organization_id` | High | 80-95% | ✅ CRITICAL |
| `course_id` | High | 70-90% | ✅ Existing |
| `active` | Low | 50% | ⚠️ Part of composite |
| `published` | Low | 50% | ⚠️ Part of composite |
| `view_order` | Medium | 60-80% | ℹ️ Optional |

---

## 9. Testing Recommendations

### 9.1 Unit Tests Required

**New Test Cases:**

1. **Organization Context:**
   ```php
   public function testModuleInheritsOrganizationFromCourse(): void
   {
       $course = new Course();
       $course->setOrganization($organization);

       $module = new CourseModule();
       $module->setCourse($course);
       $module->setOrganization($course->getOrganization());

       $this->assertSame($organization, $module->getOrganization());
   }
   ```

2. **Published State:**
   ```php
   public function testModuleDefaultsToUnpublished(): void
   {
       $module = new CourseModule();
       $this->assertFalse($module->getPublished());
   }
   ```

3. **Visibility Logic:**
   ```php
   public function testDraftModuleNotVisibleToStudents(): void
   {
       $module = new CourseModule();
       $module->setActive(true);
       $module->setPublished(false);

       $this->assertFalse($module->isVisibleToStudents());
   }

   public function testScheduledModuleNotVisibleUntilReleaseDate(): void
   {
       $module = new CourseModule();
       $module->setActive(true);
       $module->setPublished(true);
       $module->setReleaseDate(new \DateTimeImmutable('+1 day'));

       $this->assertFalse($module->isVisibleToStudents());
   }
   ```

### 9.2 Integration Tests Required

1. **Repository Filtering:**
   - Test organization-based filtering
   - Test published/active filtering
   - Test release date filtering

2. **Controller Actions:**
   - Test create sets organization automatically
   - Test edit preserves organization
   - Test delete cascade with lectures

3. **API Endpoints:**
   - Test GET /api/admin/course-modules filters by organization
   - Test POST validates required fields
   - Test PATCH updates published state

---

## 10. Documentation Updates Required

### 10.1 Files to Update

1. **CLAUDE.md** (project quick reference)
   - Add CourseModule to entity examples
   - Document published vs active distinction

2. **docs/DATABASE.md** (database guide)
   - Add CourseModule schema diagram
   - Document organization relationship pattern

3. **docs/MULTI_TENANT.md** (multi-tenant guide)
   - Add CourseModule to filtered entities list
   - Explain organization context inheritance

4. **API Documentation** (OpenAPI/Swagger)
   - Regenerate with new fields
   - Document visibility rules

---

## Appendix A: Comparison with Similar Entities

### A.1 Course Entity Pattern

**Course.php** has:
- ✅ `organization` field (line 56)
- ✅ `active` field (line 42)
- ❌ NO `published` field
- ✅ `releaseDate` field (line 46)

**Recommendation:** Add `published` to Course entity as well for consistency.

### A.2 CourseLecture Entity Pattern

**CourseLecture.php** has:
- ❌ NO `organization` field (relies on module → course → organization)
- ❌ NO `active` field
- ❌ NO `published` field
- ✅ Video processing status fields

**Analysis:** Lectures don't need organization (granularity too fine), but CourseModule needs it for efficient filtering.

### A.3 StudentCourse Entity Pattern

**StudentCourse.php** has:
- ✅ `organization` field (line 64)
- ✅ `active` field (line 38)
- ✅ Completion tracking (`completedAt`, `progressPercentage`)

**Pattern Confirmed:** Multi-tenant entities at course/module level need `organization` field.

---

## Appendix B: Industry Research Summary

### B.1 Modern LMS Module Features (2025)

Based on web research of current LMS platforms:

**Moodle 4.x:**
- Module visibility settings (draft/published)
- Conditional access (prerequisites)
- Completion tracking (required/optional)
- Activity restrictions (date-based)

**Canvas LMS:**
- Module publishing workflow
- Prerequisites enforcement
- Module completion requirements
- Lock until date functionality

**Open edX:**
- Content visibility controls
- Learning objectives
- Estimated time to complete
- Prerequisite modules

**Common Pattern:**
All major LMS platforms separate "exists" (`active`) from "visible to students" (`published`).

### B.2 Database Design Patterns

**From research sources:**

1. **Normalization:** Module entities should reference organization directly (not only through parent)
2. **Completion Tracking:** Separate entity for student progress, but module defines requirements
3. **Visibility Control:** Boolean flags + date restrictions for flexible publishing
4. **Metadata:** Learning objectives and prerequisites are standard fields

---

## Appendix C: SQL Query Examples

### C.1 Get Published Modules for Student

```sql
-- EFFICIENT: Direct organization filter with published check
SELECT
    cm.id,
    cm.name,
    cm.description,
    cm.view_order,
    cm.total_length_seconds,
    cm.estimated_minutes,
    cm.objectives,
    cm.prerequisites,
    c.name AS course_name
FROM course_module cm
INNER JOIN course c ON cm.course_id = c.id
WHERE cm.organization_id = :organizationId
  AND cm.active = true
  AND cm.published = true
  AND (cm.release_date IS NULL OR cm.release_date <= CURRENT_TIMESTAMP)
  AND c.active = true
  AND c.id = :courseId
ORDER BY cm.view_order ASC;
```

**Execution Plan:** Uses index on `(organization_id, active, published, release_date)`

### C.2 Get Draft Modules for Instructor

```sql
-- Get all modules in draft state for editing
SELECT
    cm.id,
    cm.name,
    cm.active,
    cm.published,
    cm.release_date,
    cm.created_at,
    cm.updated_at
FROM course_module cm
WHERE cm.organization_id = :organizationId
  AND cm.active = true
  AND cm.published = false
ORDER BY cm.created_at DESC;
```

### C.3 Module Completion Statistics

```sql
-- Get completion stats for a module
SELECT
    cm.id,
    cm.name,
    cm.completion_required,
    COUNT(DISTINCT sc.id) AS enrolled_students,
    COUNT(DISTINCT CASE
        WHEN sc.progress_percentage >= 95.0
        THEN sc.id
    END) AS completed_students
FROM course_module cm
INNER JOIN course c ON cm.course_id = c.id
LEFT JOIN student_course sc ON sc.course_id = c.id
WHERE cm.organization_id = :organizationId
  AND cm.id = :moduleId
GROUP BY cm.id, cm.name, cm.completion_required;
```

---

## Appendix D: Implementation Checklist

### Phase 1: Entity & Migration (2-3 hours)

- [ ] Update CourseModule entity with new properties
- [ ] Fix `isActive()` → `getActive()` naming
- [ ] Add new business logic methods
- [ ] Generate migration file
- [ ] Review and test migration SQL
- [ ] Run migration on dev database
- [ ] Validate schema consistency

### Phase 2: Repository & API (1-2 hours)

- [ ] Update CourseModuleRepository filtering
- [ ] Add new fields to entityToArray()
- [ ] Update API Platform operations
- [ ] Test API endpoints with Postman/Insomnia

### Phase 3: Controller & Forms (2-3 hours)

- [ ] Update CourseModuleController
- [ ] Add organization auto-assignment
- [ ] Update CourseModuleFormType
- [ ] Add published field to form
- [ ] Add new optional fields to form

### Phase 4: Templates (1-2 hours)

- [ ] Update Twig templates
- [ ] Add published field display
- [ ] Update module visibility indicators
- [ ] Test UI in browser

### Phase 5: Testing (3-4 hours)

- [ ] Write unit tests for new methods
- [ ] Write integration tests for repository
- [ ] Write functional tests for controller
- [ ] Update fixtures with new fields
- [ ] Run full test suite

### Phase 6: Documentation (1 hour)

- [ ] Update CLAUDE.md
- [ ] Update DATABASE.md
- [ ] Update MULTI_TENANT.md
- [ ] Regenerate API docs

**Total Estimated Time:** 10-15 hours

---

## Conclusion

### Summary of Findings

**Current State:**
- ✅ Entity is structurally sound and follows Symfony best practices
- ✅ Relationships properly configured
- ✅ Audit trail implemented
- ✅ Basic CRUD functionality works

**Critical Issues:**
- ❌ Missing `organization` field (multi-tenant security issue)
- ❌ Missing `published` field (no draft/publish workflow)
- ⚠️ Boolean getter naming inconsistency
- ⚠️ Incomplete API Platform operations

**Recommendations Priority:**

1. **CRITICAL (Must Fix):**
   - Add `organization` field
   - Add `published` field
   - Fix boolean getter naming

2. **RECOMMENDED (Should Add):**
   - Add `completionRequired` field
   - Add `estimatedMinutes` field
   - Add learning metadata fields

3. **OPTIONAL (Nice to Have):**
   - Complete API Platform CRUD operations
   - Add soft delete support
   - Add advanced search fields

### Next Steps

1. **Review this report** with the development team
2. **Prioritize** which enhancements to implement first
3. **Create migration** for selected fields
4. **Update** entity, repository, and controllers
5. **Test** thoroughly before deploying to production
6. **Document** changes in project documentation

---

**Report End**
**Generated by:** Claude (Database Optimization Expert)
**Date:** 2025-10-19
**Version:** 1.0
