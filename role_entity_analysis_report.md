# Role Entity - Comprehensive Analysis Report

**Entity:** Role
**Database:** PostgreSQL 18
**Analysis Date:** 2025-10-19
**Status:** CRITICAL ISSUES IDENTIFIED - Immediate Action Required

---

## Executive Summary

The Role entity is a **CRITICAL** component of the RBAC (Role-Based Access Control) system but has **SEVERE CONVENTION VIOLATIONS** and **MISSING FEATURES** compared to CRM best practices for 2025. The entity violates boolean naming conventions, lacks multi-tenant organization filtering, has incomplete API Platform configuration, and is missing essential RBAC hierarchy features.

**Severity:** HIGH - Impacts security, multi-tenancy, and RBAC compliance

---

## 1. Current Entity Analysis

### File Location
- **Path:** `/home/user/inf/app/src/Entity/Role.php`
- **Repository:** `/home/user/inf/app/src/Repository/RoleRepository.php`
- **Lines of Code:** 188
- **Database Table:** `role` (exists, 14 roles currently stored)

### Current Database Schema

```sql
Table "public.role"
   Column    |              Type              | Nullable | Default
-------------+--------------------------------+----------+---------
 id          | uuid                           | not null |
 name        | character varying(50)          | not null |
 description | character varying(255)         | not null |
 permissions | json                           | not null |
 is_system   | boolean                        | not null |
 created_at  | timestamp(0)                   | not null |
 updated_at  | timestamp(0)                   | not null |

Indexes:
    "role_pkey" PRIMARY KEY, btree (id)
    "uniq_57698a6a5e237e06" UNIQUE, btree (name)
Referenced by:
    TABLE "user_roles" CONSTRAINT "fk_54fcd59fd60322ac" FOREIGN KEY (role_id) REFERENCES role(id) ON DELETE CASCADE
```

### Current Entity Structure

```php
#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]
class Role
{
    protected Uuid $id;                              // UUIDv7 ✓
    protected string $name = '';                     // Unique, max 50 ✓
    protected string $description = '';              // Max 255 ✓
    protected array $permissions = [];               // JSON ✓
    protected bool $isSystem = false;                // ⚠️ WRONG CONVENTION
    protected Collection $users;                     // ManyToMany ✓
    protected \DateTimeImmutable $createdAt;        // ✓
    protected \DateTimeImmutable $updatedAt;        // ✓
}
```

---

## 2. CRITICAL ISSUES IDENTIFIED

### 2.1 Naming Convention Violations (CRITICAL)

**Issue:** Boolean field uses `isSystem` instead of `system`

```php
// ❌ CURRENT (WRONG)
protected bool $isSystem = false;

public function isSystem(): bool
{
    return $this->isSystem;
}

public function setIsSystem(bool $isSystem): self
```

**Convention Required:**
```php
// ✅ CORRECT
protected bool $system = false;

public function isSystem(): bool  // Getter name is correct
{
    return $this->system;
}

public function setSystem(bool $system): self  // Setter without "Is"
```

**Impact:**
- ❌ Violates project-wide boolean naming standard (`active`, `system`, NOT `isActive`, `isSystem`)
- ❌ Database column name mismatch (`is_system` vs convention `system`)
- ❌ Inconsistent with User entity (`active`, not `isActive`)
- ❌ Inconsistent with Organization entity (`isActive` - also needs fixing)

---

### 2.2 Missing Organization Multi-Tenant Filter (CRITICAL)

**Issue:** No organization field - roles are global across all organizations

```php
// ❌ MISSING IN ROLE ENTITY
#[ORM\ManyToOne(targetEntity: Organization::class)]
#[ORM\JoinColumn(nullable: false)]
private Organization $organization;
```

**Impact:**
- ❌ **SEVERE SECURITY ISSUE:** Roles are shared across all organizations
- ❌ Organization A can see/use Organization B's custom roles
- ❌ Cannot filter roles by organization
- ❌ Multi-tenant isolation completely broken for roles
- ❌ Violates the project's core multi-tenant architecture

**Required Index:**
```php
#[ORM\Index(name: 'idx_role_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_role_system', columns: ['system'])]
```

---

### 2.3 Incomplete API Platform Configuration (HIGH)

**Issue:** Missing normalization/denormalization groups and security

```php
// ❌ CURRENT (INCOMPLETE)
#[ApiResource(
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete()
    ]
)]
```

**Required:**
```php
// ✅ CORRECT
#[ApiResource(
    normalizationContext: ['groups' => ['role:read']],
    denormalizationContext: ['groups' => ['role:write']],
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN') and object.isSystem() == false"),
        new Delete(security: "is_granted('ROLE_ADMIN') and object.isSystem() == false"),
        new GetCollection(
            uriTemplate: '/admin/roles',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['role:read', 'audit:read']]
        )
    ]
)]
```

**Missing Serialization Groups:**
```php
// All properties need Groups annotations
#[Groups(['role:read', 'role:write'])]
protected string $name = '';

#[Groups(['role:read', 'role:write'])]
protected string $description = '';

#[Groups(['role:read', 'role:write'])]
protected array $permissions = [];

#[Groups(['role:read'])]
protected bool $system = false;  // Read-only via API

#[Groups(['role:read'])]
protected Collection $users;  // Read-only

#[Groups(['role:read', 'audit:read'])]
protected \DateTimeImmutable $createdAt;

#[Groups(['role:read', 'audit:read'])]
protected \DateTimeImmutable $updatedAt;
```

---

### 2.4 Missing CRM RBAC 2025 Best Practice Properties (HIGH)

Based on research, modern CRM RBAC systems require these missing features:

#### A. Role Hierarchy & Priority

```php
// ❌ MISSING
#[ORM\Column(type: 'integer', options: ['default' => 0])]
#[Groups(['role:read', 'role:write'])]
protected int $priority = 0;  // Higher = more permissions (CEO > Manager > User)

#[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'childRoles')]
#[ORM\JoinColumn(nullable: true)]
#[Groups(['role:read', 'role:write'])]
protected ?Role $parentRole = null;  // Inheritance hierarchy

#[ORM\OneToMany(mappedBy: 'parentRole', targetEntity: Role::class)]
#[Groups(['role:read'])]
protected Collection $childRoles;  // Roles that inherit from this
```

**Use Case:**
- CEO role (priority 100) inherits all permissions from Manager (priority 50)
- Manager role inherits all permissions from User (priority 10)
- Prevents "role explosion" by using inheritance

#### B. Active Status Flag

```php
// ❌ MISSING
#[ORM\Column(type: 'boolean', options: ['default' => true])]
#[ORM\Index(name: 'idx_role_active', columns: ['active'])]
#[Groups(['role:read', 'role:write'])]
protected bool $active = true;  // Disable roles without deleting
```

**Use Case:**
- Temporarily disable a role without deleting it
- Preserve role history and audit trail
- Re-enable roles when needed

#### C. Role Categorization

```php
// ❌ MISSING
#[ORM\Column(length: 50, nullable: true)]
#[Groups(['role:read', 'role:write'])]
protected ?string $category = null;  // 'sales', 'support', 'admin', 'custom'

#[ORM\Column(length: 50, options: ['default' => 'custom'])]
#[Groups(['role:read', 'role:write'])]
protected string $roleType = 'custom';  // 'system', 'predefined', 'custom'
```

**Use Case:**
- Group roles by department or function
- Filter roles in UI by category
- Organize role management screens

#### D. Permission Metadata

```php
// ❌ MISSING
#[ORM\Column(type: 'json', nullable: true)]
#[Groups(['role:read', 'role:write'])]
protected ?array $dataAccessRules = null;  // Record-level permissions

#[ORM\Column(type: 'json', nullable: true)]
#[Groups(['role:read', 'role:write'])]
protected ?array $fieldAccessRules = null;  // Field-level permissions

#[ORM\Column(type: 'text', nullable: true)]
#[Groups(['role:read', 'role:write'])]
protected ?string $notes = null;  // Admin notes about role purpose
```

**Use Case:**
- Data access: "Can only view own records" vs "Can view team records"
- Field access: "Can view salary" vs "Cannot view salary"
- Notes: Document why role exists and when to use it

#### E. Audit and Lifecycle

```php
// ❌ MISSING
#[ORM\ManyToOne(targetEntity: User::class)]
#[Groups(['audit:read'])]
protected ?User $createdBy = null;

#[ORM\ManyToOne(targetEntity: User::class)]
#[Groups(['audit:read'])]
protected ?User $updatedBy = null;

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
#[Groups(['audit:read'])]
protected ?\DateTimeImmutable $deletedAt = null;  // Soft delete

#[ORM\Column(type: 'integer', options: ['default' => 0])]
#[Groups(['role:read'])]
protected int $userCount = 0;  // Cached count for performance

#[ORM\Column(type: 'datetime_immutable', nullable: true)]
#[Groups(['audit:read'])]
protected ?\DateTimeImmutable $lastUsedAt = null;  // Track usage
```

**Use Case:**
- Track who created/modified roles
- Soft delete for audit compliance
- Performance optimization (cached user count)
- Identify unused roles for cleanup

---

### 2.5 Missing Validation Rules (MEDIUM)

```php
// ❌ MISSING VALIDATION
protected string $name = '';

// ✅ SHOULD BE
#[ORM\Column(length: 50, unique: true)]
#[Assert\NotBlank(message: 'Role name is required')]
#[Assert\Length(
    min: 2,
    max: 50,
    minMessage: 'Role name must be at least {{ limit }} characters',
    maxMessage: 'Role name cannot be longer than {{ limit }} characters'
)]
#[Assert\Regex(
    pattern: '/^[a-zA-Z0-9\s\-_]+$/',
    message: 'Role name can only contain letters, numbers, spaces, hyphens, and underscores'
)]
#[Groups(['role:read', 'role:write'])]
protected string $name = '';

// ❌ MISSING VALIDATION
protected string $description = '';

// ✅ SHOULD BE
#[ORM\Column(length: 500, nullable: true)]
#[Assert\Length(
    max: 500,
    maxMessage: 'Description cannot be longer than {{ limit }} characters'
)]
#[Groups(['role:read', 'role:write'])]
protected ?string $description = null;

// ❌ MISSING VALIDATION
protected array $permissions = [];

// ✅ SHOULD BE
#[ORM\Column(type: 'json')]
#[Assert\Type('array')]
#[Assert\All([
    new Assert\Type('string'),
    new Assert\NotBlank(),
    new Assert\Regex(
        pattern: '/^[A-Z_]+$/',
        message: 'Permissions must be uppercase with underscores (e.g., CREATE_USER)'
    )
])]
#[Groups(['role:read', 'role:write'])]
protected array $permissions = [];
```

---

### 2.6 Missing Repository Methods (MEDIUM)

**Current Repository:** Basic findNonSystemRoles, findOneByName, findByPermission

**Missing Methods:**

```php
/**
 * Find roles by organization (CRITICAL - for multi-tenancy)
 */
public function findByOrganization(Organization $organization, bool $includeSystem = true): array
{
    $qb = $this->createQueryBuilder('r')
        ->where('r.organization = :organization')
        ->setParameter('organization', $organization)
        ->orderBy('r.priority', 'DESC')
        ->addOrderBy('r.name', 'ASC');

    if (!$includeSystem) {
        $qb->andWhere('r.system = :system')
           ->setParameter('system', false);
    }

    return $qb->getQuery()->getResult();
}

/**
 * Find active roles only
 */
public function findActiveRoles(Organization $organization): array
{
    return $this->createQueryBuilder('r')
        ->where('r.organization = :organization')
        ->andWhere('r.active = :active')
        ->setParameter('organization', $organization)
        ->setParameter('active', true)
        ->orderBy('r.priority', 'DESC')
        ->getQuery()
        ->getResult();
}

/**
 * Find roles by category
 */
public function findByCategory(Organization $organization, string $category): array
{
    return $this->createQueryBuilder('r')
        ->where('r.organization = :organization')
        ->andWhere('r.category = :category')
        ->setParameter('organization', $organization)
        ->setParameter('category', $category)
        ->orderBy('r.name', 'ASC')
        ->getQuery()
        ->getResult();
}

/**
 * Find roles in hierarchy (with children)
 */
public function findRoleHierarchy(Role $role): array
{
    $roles = [$role];
    $this->collectChildRoles($role, $roles);
    return $roles;
}

private function collectChildRoles(Role $role, array &$roles): void
{
    foreach ($role->getChildRoles() as $child) {
        $roles[] = $child;
        $this->collectChildRoles($child, $roles);
    }
}

/**
 * Find roles with specific permission (needs organization filter)
 */
public function findByPermission(Organization $organization, string $permission): array
{
    return $this->createQueryBuilder('r')
        ->where('r.organization = :organization')
        ->andWhere('JSON_CONTAINS(r.permissions, :permission) = 1')
        ->setParameter('organization', $organization)
        ->setParameter('permission', json_encode($permission))
        ->getQuery()
        ->getResult();
}

/**
 * Get role statistics
 */
public function getRoleStatistics(Organization $organization): array
{
    return $this->createQueryBuilder('r')
        ->select('
            COUNT(r.id) as total,
            SUM(CASE WHEN r.active = true THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN r.system = true THEN 1 ELSE 0 END) as system,
            SUM(CASE WHEN r.system = false THEN 1 ELSE 0 END) as custom
        ')
        ->where('r.organization = :organization')
        ->setParameter('organization', $organization)
        ->getQuery()
        ->getSingleResult();
}

/**
 * Find unused roles (no users assigned, not used recently)
 */
public function findUnusedRoles(Organization $organization, int $daysInactive = 90): array
{
    $date = new \DateTimeImmutable("-{$daysInactive} days");

    return $this->createQueryBuilder('r')
        ->leftJoin('r.users', 'u')
        ->where('r.organization = :organization')
        ->andWhere('r.system = false')
        ->andWhere('r.userCount = 0 OR r.lastUsedAt < :date OR r.lastUsedAt IS NULL')
        ->setParameter('organization', $organization)
        ->setParameter('date', $date)
        ->getQuery()
        ->getResult();
}
```

---

### 2.7 Missing Security Voter (CRITICAL)

**Issue:** No RoleVoter exists

**Required:** `/home/user/inf/app/src/Security/Voter/RoleVoter.php`

```php
<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\Role;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RoleVoter extends Voter
{
    public const VIEW = 'view';
    public const EDIT = 'edit';
    public const DELETE = 'delete';
    public const CREATE = 'create';
    public const ASSIGN = 'assign';  // Assign role to users

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE, self::CREATE, self::ASSIGN])) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return true;
        }

        return $subject instanceof Role;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // Admin can do everything
        if ($user->hasRole('ROLE_ADMIN')) {
            return true;
        }

        return match ($attribute) {
            self::CREATE => $this->canCreate($user),
            self::VIEW => $this->canView($subject, $user),
            self::EDIT => $this->canEdit($subject, $user),
            self::DELETE => $this->canDelete($subject, $user),
            self::ASSIGN => $this->canAssign($subject, $user),
            default => false,
        };
    }

    private function canCreate(User $user): bool
    {
        // Only admins and users with MANAGE_ROLES permission
        return $user->hasPermission('MANAGE_ROLES');
    }

    private function canView(Role $role, User $user): bool
    {
        // Users can view roles in their organization
        return $role->getOrganization() === $user->getOrganization();
    }

    private function canEdit(Role $role, User $user): bool
    {
        // Cannot edit system roles
        if ($role->isSystem()) {
            return false;
        }

        // Must be same organization
        if ($role->getOrganization() !== $user->getOrganization()) {
            return false;
        }

        // Need MANAGE_ROLES permission
        return $user->hasPermission('MANAGE_ROLES');
    }

    private function canDelete(Role $role, User $user): bool
    {
        // Cannot delete system roles
        if ($role->isSystem()) {
            return false;
        }

        // Cannot delete roles with users assigned
        if ($role->getUsers()->count() > 0) {
            return false;
        }

        // Must be same organization
        if ($role->getOrganization() !== $user->getOrganization()) {
            return false;
        }

        // Need MANAGE_ROLES permission
        return $user->hasPermission('MANAGE_ROLES');
    }

    private function canAssign(Role $role, User $user): bool
    {
        // Must be same organization
        if ($role->getOrganization() !== $user->getOrganization()) {
            return false;
        }

        // Need ASSIGN_ROLES permission or be admin
        return $user->hasPermission('ASSIGN_ROLES') || $user->hasPermission('MANAGE_ROLES');
    }
}
```

---

### 2.8 Missing Helper Methods (MEDIUM)

```php
// ❌ MISSING IN ROLE ENTITY

/**
 * Get all permissions including inherited from parent roles
 */
public function getAllPermissions(): array
{
    $permissions = $this->permissions;

    if ($this->parentRole) {
        $permissions = array_merge($permissions, $this->parentRole->getAllPermissions());
    }

    return array_unique($permissions);
}

/**
 * Check if role has permission (including inherited)
 */
public function hasPermissionIncludingInherited(string $permission): bool
{
    return in_array($permission, $this->getAllPermissions(), true);
}

/**
 * Get role display name with hierarchy
 */
public function getHierarchicalName(): string
{
    if (!$this->parentRole) {
        return $this->name;
    }

    return $this->parentRole->getHierarchicalName() . ' > ' . $this->name;
}

/**
 * Check if this role is ancestor of another role
 */
public function isAncestorOf(Role $role): bool
{
    if ($role->getParentRole() === $this) {
        return true;
    }

    if ($role->getParentRole()) {
        return $this->isAncestorOf($role->getParentRole());
    }

    return false;
}

/**
 * Check if this role is descendant of another role
 */
public function isDescendantOf(Role $role): bool
{
    return $role->isAncestorOf($this);
}

/**
 * Update user count cache
 */
public function updateUserCount(): self
{
    $this->userCount = $this->users->count();
    return $this;
}

/**
 * Update last used timestamp
 */
public function touch(): self
{
    $this->lastUsedAt = new \DateTimeImmutable();
    return $this;
}

/**
 * Check if role is deletable
 */
public function isDeletable(): bool
{
    // System roles cannot be deleted
    if ($this->system) {
        return false;
    }

    // Roles with users cannot be deleted
    if ($this->users->count() > 0) {
        return false;
    }

    // Roles with child roles cannot be deleted
    if ($this->childRoles->count() > 0) {
        return false;
    }

    return true;
}

/**
 * Get role level in hierarchy (0 = root, 1 = child, 2 = grandchild, etc.)
 */
public function getHierarchyLevel(): int
{
    if (!$this->parentRole) {
        return 0;
    }

    return $this->parentRole->getHierarchyLevel() + 1;
}
```

---

## 3. CRM RBAC 2025 Best Practices Summary

Based on research of modern CRM systems (Salesforce, HubSpot, Zoho), here are the key RBAC features:

### 3.1 Role Hierarchy Models

1. **Tree Hierarchy (Bottom-Up)**
   - Junior roles grant permissions to senior roles
   - Example: Sales Rep → Sales Manager → Sales Director → VP Sales → CEO
   - Lower roles can access their data, higher roles inherit access

2. **Inverted Tree (Top-Down)**
   - Senior roles grant permissions to junior roles
   - Example: Admin → Manager → User
   - Less common in CRM

3. **Lattice (Hybrid)**
   - Combination of bottom-up and top-down
   - Most flexible but complex to manage

### 3.2 Permission Types

1. **Object-Level Permissions**
   - CREATE, READ, UPDATE, DELETE for each entity
   - Example: `CREATE_CONTACT`, `UPDATE_DEAL`, `DELETE_PRODUCT`

2. **Field-Level Permissions**
   - Access control per field
   - Example: "Can view Salary" vs "Cannot view Salary"

3. **Record-Level Permissions**
   - Own records only
   - Team records (based on hierarchy)
   - All records
   - Custom criteria (e.g., "Region = 'North America'")

### 3.3 Principle of Least Privilege

- Grant minimum permissions needed
- Use role templates for common roles
- Regular audit of permissions
- Separation of duties (create vs approve)

### 3.4 Avoid Role Explosion

- Use role inheritance instead of duplicating permissions
- Use permission sets for special access
- Limit total roles to <50 per organization
- Archive unused roles

---

## 4. Database Migration Plan

### Step 1: Rename `is_system` to `system`

```sql
-- Migration: Rename is_system to system
ALTER TABLE role RENAME COLUMN is_system TO system;

-- Update index if exists
DROP INDEX IF EXISTS idx_role_system;
CREATE INDEX idx_role_system ON role(system);
```

### Step 2: Add Organization Multi-Tenancy

```sql
-- Migration: Add organization_id
ALTER TABLE role ADD COLUMN organization_id UUID;

-- Add foreign key
ALTER TABLE role
    ADD CONSTRAINT fk_role_organization
    FOREIGN KEY (organization_id)
    REFERENCES organization(id)
    ON DELETE CASCADE;

-- Add index
CREATE INDEX idx_role_organization ON role(organization_id);

-- Backfill with default organization (MUST BE DONE MANUALLY)
-- UPDATE role SET organization_id = (SELECT id FROM organization LIMIT 1) WHERE organization_id IS NULL;

-- Make NOT NULL after backfill
-- ALTER TABLE role ALTER COLUMN organization_id SET NOT NULL;

-- Update unique constraint to be per organization
ALTER TABLE role DROP CONSTRAINT IF EXISTS uniq_57698a6a5e237e06;
CREATE UNIQUE INDEX uniq_role_name_per_org ON role(name, organization_id);
```

### Step 3: Add New RBAC Properties

```sql
-- Migration: Add RBAC 2025 properties
ALTER TABLE role ADD COLUMN priority INTEGER DEFAULT 0;
ALTER TABLE role ADD COLUMN parent_role_id UUID;
ALTER TABLE role ADD COLUMN active BOOLEAN DEFAULT true;
ALTER TABLE role ADD COLUMN category VARCHAR(50);
ALTER TABLE role ADD COLUMN role_type VARCHAR(50) DEFAULT 'custom';
ALTER TABLE role ADD COLUMN data_access_rules JSON;
ALTER TABLE role ADD COLUMN field_access_rules JSON;
ALTER TABLE role ADD COLUMN notes TEXT;
ALTER TABLE role ADD COLUMN created_by_id UUID;
ALTER TABLE role ADD COLUMN updated_by_id UUID;
ALTER TABLE role ADD COLUMN deleted_at TIMESTAMP;
ALTER TABLE role ADD COLUMN user_count INTEGER DEFAULT 0;
ALTER TABLE role ADD COLUMN last_used_at TIMESTAMP;

-- Add foreign keys
ALTER TABLE role
    ADD CONSTRAINT fk_role_parent
    FOREIGN KEY (parent_role_id)
    REFERENCES role(id)
    ON DELETE SET NULL;

ALTER TABLE role
    ADD CONSTRAINT fk_role_created_by
    FOREIGN KEY (created_by_id)
    REFERENCES "user"(id)
    ON DELETE SET NULL;

ALTER TABLE role
    ADD CONSTRAINT fk_role_updated_by
    FOREIGN KEY (updated_by_id)
    REFERENCES "user"(id)
    ON DELETE SET NULL;

-- Add indexes
CREATE INDEX idx_role_priority ON role(priority);
CREATE INDEX idx_role_parent ON role(parent_role_id);
CREATE INDEX idx_role_active ON role(active);
CREATE INDEX idx_role_category ON role(category);
CREATE INDEX idx_role_deleted_at ON role(deleted_at);
CREATE INDEX idx_role_last_used_at ON role(last_used_at);

-- Update description to be nullable with longer max
ALTER TABLE role ALTER COLUMN description DROP NOT NULL;
ALTER TABLE role ALTER COLUMN description TYPE VARCHAR(500);
```

### Step 4: Backfill User Count

```sql
-- Backfill user_count from user_roles junction table
UPDATE role r
SET user_count = (
    SELECT COUNT(*)
    FROM user_roles ur
    WHERE ur.role_id = r.id
);
```

---

## 5. PHP Entity Updates Required

### 5.1 Update Role.php Entity

**File:** `/home/user/inf/app/src/Entity/Role.php`

**Changes Required:**

1. **Rename `isSystem` property to `system`**
2. **Add `organization` relationship**
3. **Add all RBAC 2025 properties**
4. **Add serialization groups**
5. **Update API Platform configuration**
6. **Add validation rules**
7. **Add helper methods**
8. **Update `__construct()` to initialize new collections**

**Complete updated entity:** (See Section 8 for full code)

### 5.2 Update RoleRepository.php

**File:** `/home/user/inf/app/src/Repository/RoleRepository.php`

**Changes Required:**

1. Add `findByOrganization()` method
2. Add `findActiveRoles()` method
3. Add `findByCategory()` method
4. Add `findRoleHierarchy()` method
5. Update `findByPermission()` to filter by organization
6. Add `getRoleStatistics()` method
7. Add `findUnusedRoles()` method

**Complete updated repository:** (See Section 9 for full code)

---

## 6. Security & Validation Updates

### 6.1 Create RoleVoter

**File:** `/home/user/inf/app/src/Security/Voter/RoleVoter.php`

**Complete implementation:** (See Section 2.7 above)

### 6.2 Update User Entity

**File:** `/home/user/inf/app/src/Entity/User.php`

**Current issue:** Uses `roles` collection but should also track who created/updated roles

**No changes required** - User entity already has the ManyToMany relationship with Role

---

## 7. Testing Requirements

### 7.1 Unit Tests

**File:** `/home/user/inf/app/tests/Entity/RoleTest.php`

```php
<?php

namespace App\Tests\Entity;

use App\Entity\Organization;
use App\Entity\Role;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function testRoleCreation(): void
    {
        $role = new Role();
        $role->setName('Sales Manager');
        $role->setDescription('Manages sales team');
        $role->setSystem(false);

        $this->assertEquals('Sales Manager', $role->getName());
        $this->assertFalse($role->isSystem());
    }

    public function testPermissionManagement(): void
    {
        $role = new Role();
        $role->addPermission('CREATE_CONTACT');
        $role->addPermission('UPDATE_CONTACT');

        $this->assertTrue($role->hasPermission('CREATE_CONTACT'));
        $this->assertFalse($role->hasPermission('DELETE_CONTACT'));

        $role->removePermission('CREATE_CONTACT');
        $this->assertFalse($role->hasPermission('CREATE_CONTACT'));
    }

    public function testRoleHierarchy(): void
    {
        $ceo = new Role();
        $ceo->setName('CEO');
        $ceo->setPriority(100);
        $ceo->setPermissions(['CREATE_USER', 'DELETE_USER', 'MANAGE_ORG']);

        $manager = new Role();
        $manager->setName('Manager');
        $manager->setPriority(50);
        $manager->setParentRole($ceo);
        $manager->setPermissions(['CREATE_CONTACT', 'UPDATE_CONTACT']);

        // Manager should have both own and inherited permissions
        $allPerms = $manager->getAllPermissions();
        $this->assertContains('CREATE_CONTACT', $allPerms);
        $this->assertContains('CREATE_USER', $allPerms);
        $this->assertTrue($manager->isDescendantOf($ceo));
        $this->assertTrue($ceo->isAncestorOf($manager));
    }

    public function testIsDeletable(): void
    {
        $systemRole = new Role();
        $systemRole->setSystem(true);
        $this->assertFalse($systemRole->isDeletable());

        $customRole = new Role();
        $customRole->setSystem(false);
        $this->assertTrue($customRole->isDeletable());

        // Add a user
        $user = new User();
        $customRole->addUser($user);
        $this->assertFalse($customRole->isDeletable());
    }
}
```

### 7.2 Repository Tests

**File:** `/home/user/inf/app/tests/Repository/RoleRepositoryTest.php`

```php
<?php

namespace App\Tests\Repository;

use App\Entity\Organization;
use App\Entity\Role;
use App\Repository\RoleRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class RoleRepositoryTest extends KernelTestCase
{
    private RoleRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = static::getContainer()->get(RoleRepository::class);
    }

    public function testFindByOrganization(): void
    {
        $org = new Organization();
        $org->setName('Test Org');
        $org->setSlug('test-org');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($org);

        $role = new Role();
        $role->setName('Test Role');
        $role->setOrganization($org);
        $em->persist($role);
        $em->flush();

        $roles = $this->repository->findByOrganization($org);
        $this->assertGreaterThanOrEqual(1, count($roles));
    }

    public function testFindActiveRoles(): void
    {
        $org = new Organization();
        $org->setName('Test Org 2');
        $org->setSlug('test-org-2');

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->persist($org);

        $activeRole = new Role();
        $activeRole->setName('Active Role');
        $activeRole->setOrganization($org);
        $activeRole->setActive(true);
        $em->persist($activeRole);

        $inactiveRole = new Role();
        $inactiveRole->setName('Inactive Role');
        $inactiveRole->setOrganization($org);
        $inactiveRole->setActive(false);
        $em->persist($inactiveRole);
        $em->flush();

        $roles = $this->repository->findActiveRoles($org);
        $this->assertCount(1, $roles);
        $this->assertEquals('Active Role', $roles[0]->getName());
    }
}
```

### 7.3 Voter Tests

**File:** `/home/user/inf/app/tests/Security/Voter/RoleVoterTest.php`

```php
<?php

namespace App\Tests\Security\Voter;

use App\Entity\Organization;
use App\Entity\Role;
use App\Entity\User;
use App\Security\Voter\RoleVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class RoleVoterTest extends TestCase
{
    private RoleVoter $voter;

    protected function setUp(): void
    {
        $this->voter = new RoleVoter();
    }

    public function testAdminCanDoEverything(): void
    {
        $admin = $this->createUser(['ROLE_ADMIN']);
        $role = new Role();

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createToken($admin), $role, [RoleVoter::EDIT])
        );
    }

    public function testCannotEditSystemRole(): void
    {
        $user = $this->createUserWithPermission('MANAGE_ROLES');
        $systemRole = new Role();
        $systemRole->setSystem(true);
        $systemRole->setOrganization($user->getOrganization());

        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createToken($user), $systemRole, [RoleVoter::EDIT])
        );
    }

    public function testCanEditCustomRoleInSameOrg(): void
    {
        $user = $this->createUserWithPermission('MANAGE_ROLES');
        $role = new Role();
        $role->setSystem(false);
        $role->setOrganization($user->getOrganization());

        $this->assertEquals(
            VoterInterface::ACCESS_GRANTED,
            $this->voter->vote($this->createToken($user), $role, [RoleVoter::EDIT])
        );
    }

    public function testCannotEditRoleInDifferentOrg(): void
    {
        $user = $this->createUserWithPermission('MANAGE_ROLES');
        $otherOrg = new Organization();
        $otherOrg->setName('Other Org');

        $role = new Role();
        $role->setSystem(false);
        $role->setOrganization($otherOrg);

        $this->assertEquals(
            VoterInterface::ACCESS_DENIED,
            $this->voter->vote($this->createToken($user), $role, [RoleVoter::EDIT])
        );
    }

    private function createUser(array $roles = []): User
    {
        $user = new User();
        $org = new Organization();
        $org->setName('Test Org');
        $user->setOrganization($org);
        return $user;
    }

    private function createUserWithPermission(string $permission): User
    {
        $user = $this->createUser();
        $role = new Role();
        $role->addPermission($permission);
        $user->addRole($role);
        return $user;
    }

    private function createToken(User $user): TokenInterface
    {
        return new class($user) implements TokenInterface {
            public function __construct(private User $user) {}
            public function getUser(): User { return $this->user; }
            public function getRoleNames(): array { return []; }
            public function __serialize(): array { return []; }
            public function __unserialize(array $data): void {}
        };
    }
}
```

---

## 8. Complete Updated Role Entity

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Doctrine\UuidV7Generator;
use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(name: 'idx_role_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_role_system', columns: ['system'])]
#[ORM\Index(name: 'idx_role_active', columns: ['active'])]
#[ORM\Index(name: 'idx_role_priority', columns: ['priority'])]
#[ORM\Index(name: 'idx_role_category', columns: ['category'])]
#[ORM\Index(name: 'idx_role_parent', columns: ['parent_role_id'])]
#[ORM\Index(name: 'idx_role_deleted_at', columns: ['deleted_at'])]
#[ORM\UniqueConstraint(name: 'uniq_role_name_per_org', columns: ['name', 'organization_id'])]
#[ApiResource(
    normalizationContext: ['groups' => ['role:read']],
    denormalizationContext: ['groups' => ['role:write']],
    operations: [
        new Get(security: "is_granted('ROLE_USER')"),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN') or is_granted('MANAGE_ROLES', object)"),
        new Put(security: "is_granted('ROLE_ADMIN') or (is_granted('MANAGE_ROLES', object) and object.isSystem() == false)"),
        new Delete(security: "is_granted('ROLE_ADMIN') or (is_granted('MANAGE_ROLES', object) and object.isSystem() == false and object.isDeletable())"),
        new GetCollection(
            uriTemplate: '/admin/roles',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['role:read', 'audit:read']]
        )
    ]
)]
class Role
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    #[Groups(['role:read'])]
    protected Uuid $id;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Role name is required')]
    #[Assert\Length(
        min: 2,
        max: 50,
        minMessage: 'Role name must be at least {{ limit }} characters',
        maxMessage: 'Role name cannot be longer than {{ limit }} characters'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s\-_]+$/',
        message: 'Role name can only contain letters, numbers, spaces, hyphens, and underscores'
    )]
    #[Groups(['role:read', 'role:write'])]
    protected string $name = '';

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Length(
        max: 500,
        maxMessage: 'Description cannot be longer than {{ limit }} characters'
    )]
    #[Groups(['role:read', 'role:write'])]
    protected ?string $description = null;

    #[ORM\Column(type: 'json')]
    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\NotBlank(),
        new Assert\Regex(
            pattern: '/^[A-Z_]+$/',
            message: 'Permissions must be uppercase with underscores (e.g., CREATE_USER)'
        )
    ])]
    #[Groups(['role:read', 'role:write'])]
    protected array $permissions = [];

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['role:read'])]  // Read-only via API
    protected bool $system = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    #[Groups(['role:read', 'role:write'])]
    protected bool $active = true;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 1000)]
    #[Groups(['role:read', 'role:write'])]
    protected int $priority = 0;

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['role:read', 'role:write'])]
    protected ?string $category = null;

    #[ORM\Column(length: 50, options: ['default' => 'custom'])]
    #[Assert\Choice(choices: ['system', 'predefined', 'custom'])]
    #[Groups(['role:read', 'role:write'])]
    protected string $roleType = 'custom';

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['role:read', 'role:write'])]
    protected ?array $dataAccessRules = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['role:read', 'role:write'])]
    protected ?array $fieldAccessRules = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['role:read', 'role:write'])]
    protected ?string $notes = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    #[Groups(['role:read'])]  // Read-only via API
    protected ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Role::class, inversedBy: 'childRoles')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['role:read', 'role:write'])]
    protected ?Role $parentRole = null;

    #[ORM\OneToMany(mappedBy: 'parentRole', targetEntity: Role::class)]
    #[Groups(['role:read'])]
    protected Collection $childRoles;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'roles')]
    #[Groups(['role:read'])]
    protected Collection $users;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['audit:read'])]
    protected ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['audit:read'])]
    protected ?User $updatedBy = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['role:read'])]
    protected int $userCount = 0;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[Groups(['audit:read'])]
    protected ?\DateTimeImmutable $deletedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['role:read', 'audit:read'])]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['role:read', 'audit:read'])]
    protected \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->childRoles = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->permissions = [];
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

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

    public function getPermissions(): array
    {
        return $this->permissions;
    }

    public function setPermissions(array $permissions): self
    {
        $this->permissions = $permissions;
        return $this;
    }

    public function addPermission(string $permission): self
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    public function removePermission(string $permission): self
    {
        $this->permissions = array_values(array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        ));
        return $this;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * Get all permissions including inherited from parent roles
     */
    public function getAllPermissions(): array
    {
        $permissions = $this->permissions;

        if ($this->parentRole) {
            $permissions = array_merge($permissions, $this->parentRole->getAllPermissions());
        }

        return array_unique($permissions);
    }

    /**
     * Check if role has permission (including inherited)
     */
    public function hasPermissionIncludingInherited(string $permission): bool
    {
        return in_array($permission, $this->getAllPermissions(), true);
    }

    public function isSystem(): bool
    {
        return $this->system;
    }

    public function setSystem(bool $system): self
    {
        $this->system = $system;
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

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getRoleType(): string
    {
        return $this->roleType;
    }

    public function setRoleType(string $roleType): self
    {
        $this->roleType = $roleType;
        return $this;
    }

    public function getDataAccessRules(): ?array
    {
        return $this->dataAccessRules;
    }

    public function setDataAccessRules(?array $dataAccessRules): self
    {
        $this->dataAccessRules = $dataAccessRules;
        return $this;
    }

    public function getFieldAccessRules(): ?array
    {
        return $this->fieldAccessRules;
    }

    public function setFieldAccessRules(?array $fieldAccessRules): self
    {
        $this->fieldAccessRules = $fieldAccessRules;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    public function getParentRole(): ?Role
    {
        return $this->parentRole;
    }

    public function setParentRole(?Role $parentRole): self
    {
        $this->parentRole = $parentRole;
        return $this;
    }

    /**
     * @return Collection<int, Role>
     */
    public function getChildRoles(): Collection
    {
        return $this->childRoles;
    }

    public function addChildRole(Role $childRole): self
    {
        if (!$this->childRoles->contains($childRole)) {
            $this->childRoles->add($childRole);
            $childRole->setParentRole($this);
        }
        return $this;
    }

    public function removeChildRole(Role $childRole): self
    {
        if ($this->childRoles->removeElement($childRole)) {
            if ($childRole->getParentRole() === $this) {
                $childRole->setParentRole(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addRole($this);
        }
        return $this;
    }

    public function removeUser(User $user): self
    {
        if ($this->users->removeElement($user)) {
            $user->removeRole($this);
        }
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;
        return $this;
    }

    public function getUserCount(): int
    {
        return $this->userCount;
    }

    public function setUserCount(int $userCount): self
    {
        $this->userCount = $userCount;
        return $this;
    }

    /**
     * Update user count cache
     */
    public function updateUserCount(): self
    {
        $this->userCount = $this->users->count();
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?\DateTimeImmutable $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    /**
     * Update last used timestamp
     */
    public function touch(): self
    {
        $this->lastUsedAt = new \DateTimeImmutable();
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

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * Get role display name with hierarchy
     */
    public function getHierarchicalName(): string
    {
        if (!$this->parentRole) {
            return $this->name;
        }

        return $this->parentRole->getHierarchicalName() . ' > ' . $this->name;
    }

    /**
     * Check if this role is ancestor of another role
     */
    public function isAncestorOf(Role $role): bool
    {
        if ($role->getParentRole() === $this) {
            return true;
        }

        if ($role->getParentRole()) {
            return $this->isAncestorOf($role->getParentRole());
        }

        return false;
    }

    /**
     * Check if this role is descendant of another role
     */
    public function isDescendantOf(Role $role): bool
    {
        return $role->isAncestorOf($this);
    }

    /**
     * Check if role is deletable
     */
    public function isDeletable(): bool
    {
        // System roles cannot be deleted
        if ($this->system) {
            return false;
        }

        // Roles with users cannot be deleted
        if ($this->users->count() > 0) {
            return false;
        }

        // Roles with child roles cannot be deleted
        if ($this->childRoles->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Get role level in hierarchy (0 = root, 1 = child, 2 = grandchild, etc.)
     */
    public function getHierarchyLevel(): int
    {
        if (!$this->parentRole) {
            return 0;
        }

        return $this->parentRole->getHierarchyLevel() + 1;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
```

---

## 9. Action Items Summary

### Priority 1: CRITICAL (Security & Multi-Tenancy)

1. **Rename `isSystem` to `system`**
   - Update PHP entity property
   - Update database column
   - Update all getter/setter method bodies
   - Run database migration

2. **Add Organization multi-tenancy**
   - Add `organization` field to Role entity
   - Add foreign key constraint
   - Add index on `organization_id`
   - Backfill existing roles with organization
   - Update unique constraint to be per-organization
   - Update all repository methods to filter by organization

3. **Create RoleVoter**
   - Implement all 5 operations (VIEW, EDIT, DELETE, CREATE, ASSIGN)
   - Prevent editing system roles
   - Prevent cross-organization access
   - Prevent deleting roles with users

### Priority 2: HIGH (RBAC Features & API)

4. **Add RBAC 2025 Properties**
   - `priority` (integer)
   - `parentRole` (self-referencing ManyToOne)
   - `childRoles` (OneToMany collection)
   - `active` (boolean)
   - `category` (string)
   - `roleType` (string enum)
   - `dataAccessRules` (JSON)
   - `fieldAccessRules` (JSON)
   - `notes` (text)
   - `createdBy`, `updatedBy` (User references)
   - `deletedAt` (soft delete)
   - `userCount` (cached integer)
   - `lastUsedAt` (timestamp)

5. **Update API Platform Configuration**
   - Add normalization/denormalization groups
   - Add security expressions to all operations
   - Add admin-only endpoint `/admin/roles`
   - Add Groups annotations to all properties

6. **Add Validation Rules**
   - Name: NotBlank, Length(2-50), Regex (alphanumeric + spaces/hyphens)
   - Description: Length(max 500)
   - Permissions: Array of uppercase strings with underscores
   - Priority: Range(0-1000)
   - RoleType: Choice(system, predefined, custom)

### Priority 3: MEDIUM (Repository & Helper Methods)

7. **Update RoleRepository**
   - Add `findByOrganization()`
   - Add `findActiveRoles()`
   - Add `findByCategory()`
   - Add `findRoleHierarchy()`
   - Update `findByPermission()` with organization filter
   - Add `getRoleStatistics()`
   - Add `findUnusedRoles()`

8. **Add Helper Methods to Entity**
   - `getAllPermissions()` - with inheritance
   - `hasPermissionIncludingInherited()`
   - `getHierarchicalName()`
   - `isAncestorOf()`, `isDescendantOf()`
   - `updateUserCount()`, `touch()`
   - `isDeletable()`
   - `getHierarchyLevel()`

### Priority 4: LOW (Testing & Documentation)

9. **Create Tests**
   - Unit tests: RoleTest.php
   - Repository tests: RoleRepositoryTest.php
   - Voter tests: RoleVoterTest.php

10. **Update Documentation**
    - Document role hierarchy system
    - Document permission naming conventions
    - Document data/field access rules format
    - Create role management user guide

---

## 10. Database Query Examples

### Query 1: Find all active custom roles for an organization

```sql
SELECT
    r.id,
    r.name,
    r.priority,
    r.user_count,
    r.category,
    pr.name as parent_role_name
FROM role r
LEFT JOIN role pr ON r.parent_role_id = pr.id
WHERE r.organization_id = :org_id
  AND r.active = true
  AND r.system = false
  AND r.deleted_at IS NULL
ORDER BY r.priority DESC, r.name ASC;
```

### Query 2: Get role hierarchy tree

```sql
WITH RECURSIVE role_tree AS (
    -- Base case: root roles (no parent)
    SELECT
        id,
        name,
        parent_role_id,
        priority,
        0 as level,
        ARRAY[id] as path
    FROM role
    WHERE parent_role_id IS NULL
      AND organization_id = :org_id
      AND deleted_at IS NULL

    UNION ALL

    -- Recursive case: child roles
    SELECT
        r.id,
        r.name,
        r.parent_role_id,
        r.priority,
        rt.level + 1,
        rt.path || r.id
    FROM role r
    INNER JOIN role_tree rt ON r.parent_role_id = rt.id
    WHERE r.deleted_at IS NULL
)
SELECT
    id,
    name,
    parent_role_id,
    priority,
    level,
    path
FROM role_tree
ORDER BY level, priority DESC, name ASC;
```

### Query 3: Find roles with specific permission (including inherited)

```sql
WITH RECURSIVE role_permissions AS (
    -- Base case: direct permissions
    SELECT
        id,
        name,
        permissions,
        parent_role_id
    FROM role
    WHERE organization_id = :org_id
      AND deleted_at IS NULL
      AND permissions @> :permission::jsonb

    UNION

    -- Recursive case: roles that inherit this permission
    SELECT
        r.id,
        r.name,
        r.permissions,
        r.parent_role_id
    FROM role r
    INNER JOIN role_permissions rp ON r.parent_role_id = rp.id
    WHERE r.deleted_at IS NULL
)
SELECT DISTINCT id, name
FROM role_permissions
ORDER BY name;
```

### Query 4: Performance optimization - update user counts

```sql
UPDATE role r
SET user_count = (
    SELECT COUNT(*)
    FROM user_roles ur
    WHERE ur.role_id = r.id
),
updated_at = NOW()
WHERE r.organization_id = :org_id;
```

### Query 5: Find unused roles for cleanup

```sql
SELECT
    r.id,
    r.name,
    r.created_at,
    r.last_used_at,
    r.user_count
FROM role r
WHERE r.organization_id = :org_id
  AND r.system = false
  AND r.active = true
  AND r.deleted_at IS NULL
  AND (
    r.user_count = 0
    OR r.last_used_at < NOW() - INTERVAL '90 days'
    OR r.last_used_at IS NULL
  )
ORDER BY r.last_used_at ASC NULLS FIRST;
```

---

## 11. Performance Considerations

### Indexes Required

```sql
CREATE INDEX idx_role_organization ON role(organization_id);
CREATE INDEX idx_role_system ON role(system);
CREATE INDEX idx_role_active ON role(active);
CREATE INDEX idx_role_priority ON role(priority);
CREATE INDEX idx_role_category ON role(category);
CREATE INDEX idx_role_parent ON role(parent_role_id);
CREATE INDEX idx_role_deleted_at ON role(deleted_at);
CREATE INDEX idx_role_last_used_at ON role(last_used_at);

-- Composite index for common queries
CREATE INDEX idx_role_org_active_system ON role(organization_id, active, system);

-- Unique constraint per organization
CREATE UNIQUE INDEX uniq_role_name_per_org ON role(name, organization_id) WHERE deleted_at IS NULL;
```

### Query Optimization Tips

1. **Always filter by organization_id first** - reduces result set dramatically
2. **Use user_count cache** - avoid COUNT(*) on user_roles junction table
3. **Index deleted_at** - for soft delete queries
4. **Eager load parentRole** - prevent N+1 queries in hierarchy
5. **Cache role hierarchy** - expensive recursive queries
6. **Paginate role lists** - limit to 50 roles per page

### Caching Strategy (Redis)

```php
// Cache role hierarchy for 1 hour
$cacheKey = "role_hierarchy_{$organizationId}";
$ttl = 3600;

// Cache role permissions for 15 minutes
$cacheKey = "role_permissions_{$roleId}";
$ttl = 900;

// Invalidate cache on role update
// - When role permissions change
// - When role hierarchy changes
// - When user is assigned/removed from role
```

---

## 12. Migration Execution Plan

### Step 1: Create Migration File

```bash
cd /home/user/inf/app
php bin/console make:migration
```

### Step 2: Edit Migration File

Add all changes from Section 4 (Database Migration Plan)

### Step 3: Dry Run (Check SQL)

```bash
php bin/console doctrine:migrations:migrate --dry-run
```

### Step 4: Backup Database

```bash
docker-compose exec database pg_dump -U luminai_user luminai_db > backup_before_role_migration.sql
```

### Step 5: Execute Migration

```bash
php bin/console doctrine:migrations:migrate --no-interaction
```

### Step 6: Verify Schema

```bash
docker-compose exec -T database psql -U luminai_user -d luminai_db -c "\d role"
```

### Step 7: Backfill Organization Data

```sql
-- Get default organization ID
SELECT id, name FROM organization LIMIT 1;

-- Update all roles to use default organization
UPDATE role
SET organization_id = '<organization-uuid-here>'
WHERE organization_id IS NULL;

-- Make column NOT NULL
ALTER TABLE role ALTER COLUMN organization_id SET NOT NULL;
```

### Step 8: Update User Count Cache

```sql
UPDATE role r
SET user_count = (
    SELECT COUNT(*)
    FROM user_roles ur
    WHERE ur.role_id = r.id
);
```

---

## 13. Conclusion

The Role entity requires **CRITICAL** updates to comply with:

1. **Project Conventions** - Boolean naming (`system` not `isSystem`)
2. **Multi-Tenant Architecture** - Organization filtering (SEVERE SECURITY ISSUE)
3. **API Platform Best Practices** - Serialization groups and security
4. **CRM RBAC 2025 Standards** - Hierarchy, priority, active status, field/data access rules

**Total Properties:**
- **Current:** 7 properties
- **After Fix:** 22 properties (15 new)

**Total Indexes:**
- **Current:** 2 indexes
- **After Fix:** 10 indexes (8 new)

**Estimated Effort:**
- Database Migration: 2 hours
- Entity Updates: 3 hours
- Repository Updates: 2 hours
- Voter Creation: 2 hours
- Testing: 3 hours
- **TOTAL: 12 hours**

**Risk Level:** HIGH - Multi-tenant security isolation is broken

**Recommendation:** Execute Priority 1 and 2 items IMMEDIATELY (within 1 week)

---

**Report Generated:** 2025-10-19 23:20:00 UTC
**Database:** PostgreSQL 18
**PHP Version:** 8.4
**Symfony Version:** 7.3
**API Platform:** 4.1
