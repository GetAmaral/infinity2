# DATABASE & DOCTRINE GUIDE

Complete guide to database patterns, entities, migrations, and Doctrine ORM in Luminai.

---

## Table of Contents

- [UUIDv7 Entity Pattern](#uuidv7-entity-pattern)
- [Entity Traits](#entity-traits)
- [Custom DQL Functions](#custom-dql-functions)
- [Soft Delete System](#soft-delete-system)
- [Audit Trail System](#audit-trail-system)
- [Entity Relationships](#entity-relationships)
- [Database Migrations](#database-migrations)
- [Doctrine Best Practices](#doctrine-best-practices)
- [Troubleshooting](#troubleshooting)

---

## UUIDv7 Entity Pattern

All entities in Luminai use **UUIDv7** (time-ordered UUIDs) for better database performance and clustering.

### **Base Entity Template**

```php
<?php
namespace App\Entity;

use App\Doctrine\UuidV7Generator;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EntityRepository::class)]
#[ORM\HasLifecycleCallbacks]
abstract class EntityBase
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    protected Uuid $id;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    protected \DateTimeImmutable $updatedAt;

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

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
```

### **Creating a New Entity**

```php
<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MyEntityRepository::class)]
class MyEntity extends EntityBase
{
    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

    // Getters and setters...
}
```

### **Benefits of UUIDv7**

✅ **Time-ordered**: Better database clustering than UUIDv4
✅ **No collisions**: Safe for distributed systems
✅ **Chronological sorting**: Natural sort order by creation time
✅ **PostgreSQL native**: Excellent index performance

---

## Entity Traits

Luminai provides reusable traits for common entity functionality.

### **AuditTrait - User Tracking**

Automatically tracks **who** created/updated entities and **when**.

**File:** `/src/Entity/Trait/AuditTrait.php`

```php
<?php
namespace App\Entity\Trait;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

trait AuditTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['audit:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['audit:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['audit:read'])]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['audit:read'])]
    private ?User $updatedBy = null;

    public function initializeAuditFields(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters and setters...
}
```

**Usage:**

```php
use App\Entity\Trait\AuditTrait;

#[ORM\Entity]
class MyEntity extends EntityBase
{
    use AuditTrait;

    public function __construct()
    {
        parent::__construct();
        $this->initializeAuditFields();
    }
}

// Automatically populated by AuditSubscriber:
$entity->getCreatedBy();  // User who created
$entity->getUpdatedBy();  // User who last updated
$entity->getCreatedAt();  // Creation timestamp
$entity->getUpdatedAt();  // Last update timestamp
```

### **SoftDeletableTrait - Data Preservation**

Prevents permanent deletion for compliance and audit trails.

**File:** `/src/Entity/Trait/SoftDeletableTrait.php`

```php
<?php
namespace App\Entity\Trait;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;

trait SoftDeletableTrait
{
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $deletedBy = null;

    public function softDelete(User $user): void
    {
        $this->deletedAt = new \DateTimeImmutable();
        $this->deletedBy = $user;
    }

    public function restore(): void
    {
        $this->deletedAt = null;
        $this->deletedBy = null;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    // Getters...
}
```

**Usage:**

```php
use App\Entity\Trait\SoftDeletableTrait;

#[ORM\Entity]
class MyEntity extends EntityBase
{
    use SoftDeletableTrait;
}

// Calling remove() performs soft delete (via SoftDeleteSubscriber)
$entityManager->remove($entity);
$entityManager->flush();
// Entity still in database with deletedAt timestamp

// Check if deleted
if ($entity->isDeleted()) {
    echo "Deleted at: " . $entity->getDeletedAt()->format('Y-m-d H:i:s');
    echo "Deleted by: " . $entity->getDeletedBy()->getName();
}

// Restore deleted entity
$entity->restore();
$entityManager->flush();

// Query only active records
$qb = $repository->createQueryBuilder('e')
    ->where('e.deletedAt IS NULL');

// Query only deleted records
$qb = $repository->createQueryBuilder('e')
    ->where('e.deletedAt IS NOT NULL');
```

**Automatic Behavior:**

The `SoftDeleteSubscriber` intercepts `$em->remove()` calls:

```php
// File: src/EventSubscriber/SoftDeleteSubscriber.php

#[AsEventListener(event: Events::preRemove)]
public function onPreRemove(PreRemoveEventArgs $args): void
{
    $entity = $args->getObject();

    if (!$this->hasSoftDeletableTrait($entity)) {
        return; // Allow hard delete
    }

    // Cancel hard delete
    $args->getObjectManager()->detach($entity);

    // Perform soft delete
    $user = $this->security->getUser();
    $entity->softDelete($user);

    // Persist and log
    $args->getObjectManager()->persist($entity);
    // AuditEventMessage dispatched automatically
}
```

---

## Custom DQL Functions

Luminai extends Doctrine with PostgreSQL-specific functions.

### **UNACCENT() - Accent-Insensitive Search**

**Purpose:** Search without worrying about accents (José matches Jose).

**Configuration:**

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        dql:
            string_functions:
                unaccent: App\Doctrine\DQL\UnaccentFunction
```

**Usage:**

```php
// Search users by name (accent-insensitive)
$qb = $userRepository->createQueryBuilder('u')
    ->where('UNACCENT(u.name) LIKE UNACCENT(:search)')
    ->setParameter('search', '%Jose%');
// Matches: José, Jose, Josè, etc.

// Organization search (case and accent insensitive)
$qb = $orgRepository->createQueryBuilder('o')
    ->where('LOWER(UNACCENT(o.name)) LIKE LOWER(UNACCENT(:search))')
    ->setParameter('search', '%acme%');
// Matches: ACME Corporation, Ácme Corp, acmé, etc.
```

**Implementation:**

```php
// File: src/Doctrine/DQL/UnaccentFunction.php
namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class UnaccentFunction extends FunctionNode
{
    public $stringPrimary;

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'UNACCENT(' . $this->stringPrimary->dispatch($sqlWalker) . ')';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->stringPrimary = $parser->StringPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}
```

### **EXTRACT() - Date/Time Component Extraction**

**Purpose:** Extract components from timestamps for analytics.

**Configuration:**

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        dql:
            numeric_functions:
                extract: App\Doctrine\DQL\ExtractFunction
```

**Usage:**

```php
// Hourly activity distribution
$qb = $auditLogRepository->createQueryBuilder('a')
    ->select('EXTRACT(HOUR FROM a.createdAt) as hour')
    ->addSelect('COUNT(a.id) as count')
    ->groupBy('hour')
    ->orderBy('hour', 'ASC');

// Monthly revenue report
$qb = $orderRepository->createQueryBuilder('o')
    ->select('EXTRACT(YEAR FROM o.createdAt) as year')
    ->addSelect('EXTRACT(MONTH FROM o.createdAt) as month')
    ->addSelect('SUM(o.total) as revenue')
    ->groupBy('year, month');

// Day of week analysis
$qb = $repository->createQueryBuilder('e')
    ->select('EXTRACT(DOW FROM e.createdAt) as day_of_week')
    ->addSelect('COUNT(e.id) as count')
    ->groupBy('day_of_week');
// DOW: 0=Sunday, 1=Monday, ..., 6=Saturday
```

---

## Soft Delete System

Complete data preservation for compliance and audit requirements.

### **Implementation Architecture**

```
┌─────────────────────────────────────────┐
│ Controller: $em->remove($entity)        │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ SoftDeleteSubscriber::onPreRemove()     │
│ - Detects SoftDeletableTrait            │
│ - Cancels hard delete ($em->detach())   │
│ - Sets deletedAt + deletedBy            │
│ - Re-persists entity                    │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ AuditSubscriber::onPreUpdate()          │
│ - Logs deletion to audit trail          │
│ - Dispatches AuditEventMessage          │
└─────────────────────────────────────────┘
```

### **Query Patterns**

```php
// Repository method to exclude soft-deleted
public function findActive(): array
{
    return $this->createQueryBuilder('e')
        ->where('e.deletedAt IS NULL')
        ->getQuery()
        ->getResult();
}

// Find recently deleted (last 30 days)
public function findRecentlyDeleted(): array
{
    $since = new \DateTimeImmutable('-30 days');

    return $this->createQueryBuilder('e')
        ->where('e.deletedAt IS NOT NULL')
        ->andWhere('e.deletedAt >= :since')
        ->setParameter('since', $since)
        ->getQuery()
        ->getResult();
}

// Restore by ID
public function restore(string $id): void
{
    $entity = $this->find($id);
    if ($entity && $entity->isDeleted()) {
        $entity->restore();
        $this->getEntityManager()->flush();
    }
}
```

### **Permanent Deletion**

To permanently delete soft-deleted records:

```php
// Permanently delete old soft-deleted records (e.g., older than 1 year)
public function permanentlyDeleteOld(): int
{
    $threshold = new \DateTimeImmutable('-1 year');

    return $this->createQueryBuilder('e')
        ->delete()
        ->where('e.deletedAt IS NOT NULL')
        ->andWhere('e.deletedAt < :threshold')
        ->setParameter('threshold', $threshold)
        ->getQuery()
        ->execute();
}
```

---

## Audit Trail System

Automatic tracking of **who** modified entities and **when**.

### **Architecture**

```
┌──────────────────────────────────────────┐
│ Entity with AuditTrait                   │
│ - createdAt, updatedAt                   │
│ - createdBy, updatedBy                   │
└────────────────┬─────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────┐
│ AuditSubscriber::prePersist()            │
│ - Sets createdBy = current user          │
│ - Sets updatedBy = current user          │
└────────────────┬─────────────────────────┘
                 │
                 ▼
┌──────────────────────────────────────────┐
│ AuditSubscriber::preUpdate()             │
│ - Sets updatedBy = current user          │
│ - Logs field changes to AuditLog         │
│ - Dispatches async AuditEventMessage     │
└──────────────────────────────────────────┘
```

### **Usage Example**

```php
use App\Entity\Trait\AuditTrait;

#[ORM\Entity]
class Course extends EntityBase
{
    use AuditTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    public function __construct()
    {
        parent::__construct();
        $this->initializeAuditFields();
    }
}

// Create course (automatic audit tracking)
$course = new Course();
$course->setName('Introduction to PHP');
$entityManager->persist($course);
$entityManager->flush();

// Automatically populated:
// - createdBy = currently authenticated user
// - updatedBy = currently authenticated user
// - createdAt = current timestamp
// - updatedAt = current timestamp

// Update course
$course->setName('Advanced PHP');
$entityManager->flush();

// Automatically updated:
// - updatedBy = current user
// - updatedAt = new timestamp
// - AuditLog entry created with old/new values
```

### **Querying Audit History**

```php
// Get all changes to a course
$auditLogs = $auditLogRepository->findBy([
    'entityClass' => Course::class,
    'entityId' => $course->getId()->toString(),
], ['createdAt' => 'DESC']);

foreach ($auditLogs as $log) {
    echo $log->getAction(); // entity_created, entity_updated, entity_deleted
    echo $log->getUser()->getName(); // Who made the change
    echo $log->getCreatedAt()->format('Y-m-d H:i:s'); // When

    // Get specific field changes
    if ($log->hasChangeForField('name')) {
        $oldValue = $log->getOldValue('name');
        $newValue = $log->getNewValue('name');
        echo "Name changed from '$oldValue' to '$newValue'";
    }
}
```

---

## Entity Relationships

### **Hierarchy Overview**

```
Organization (Multi-Tenant Root)
├── User (ManyToOne)
│   ├── Role (ManyToMany)
│   └── AuditLog (created/updated tracking)
├── Course (ManyToOne)
│   ├── CourseModule (OneToMany)
│   │   └── CourseLecture (OneToMany)
│   │       └── StudentLecture (OneToMany)
│   └── StudentCourse (OneToMany)
│       └── StudentLecture (OneToMany)
└── TreeFlow (ManyToOne)
    └── Step (OneToMany)
        ├── StepQuestion (OneToMany)
        │   └── StepFewShot (OneToMany)
        ├── StepInput (OneToMany)
        └── StepOutput (OneToMany)
```

### **Relationship Patterns**

**ManyToOne (Most Common):**

```php
#[ORM\ManyToOne(targetEntity: Organization::class)]
#[ORM\JoinColumn(nullable: false)]
private Organization $organization;
```

**OneToMany with Cascade:**

```php
#[ORM\OneToMany(
    targetEntity: CourseModule::class,
    mappedBy: 'course',
    cascade: ['persist', 'remove'],
    orphanRemoval: true
)]
#[ORM\OrderBy(['viewOrder' => 'ASC'])]
private Collection $modules;
```

**ManyToMany (Roles):**

```php
#[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
#[ORM\JoinTable(name: 'user_role')]
private Collection $roles;
```

---

## Database Migrations

### **Creating Migrations**

```bash
# Generate migration from entity changes
php bin/console make:migration --no-interaction

# Review migration file in migrations/
# Then execute:
php bin/console doctrine:migrations:migrate --no-interaction
```

### **Migration Best Practices**

1. **Never modify executed migrations** - Create a new migration instead
2. **Review auto-generated SQL** - Ensure it's safe for production
3. **Test rollback** - Verify `down()` method works
4. **Use transactions** - Migrations are transactional by default
5. **Add indexes** - For foreign keys and frequently queried columns

### **Example Migration**

```php
// migrations/Version20250105120000.php

public function up(Schema $schema): void
{
    $this->addSql('CREATE TABLE my_entity (
        id UUID NOT NULL,
        organization_id UUID NOT NULL,
        name VARCHAR(255) NOT NULL,
        created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
        updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
        PRIMARY KEY(id)
    )');

    $this->addSql('CREATE INDEX IDX_MY_ENTITY_ORG ON my_entity (organization_id)');

    $this->addSql('ALTER TABLE my_entity
        ADD CONSTRAINT FK_MY_ENTITY_ORG
        FOREIGN KEY (organization_id)
        REFERENCES organization (id)
        NOT DEFERRABLE INITIALLY IMMEDIATE');
}

public function down(Schema $schema): void
{
    $this->addSql('DROP TABLE my_entity');
}
```

---

## Doctrine Best Practices

### **1. Always Use Query Builder**

❌ **Bad:**
```php
$dql = "SELECT u FROM App\Entity\User u WHERE u.name = '$name'"; // SQL injection!
```

✅ **Good:**
```php
$qb = $userRepository->createQueryBuilder('u')
    ->where('u.name = :name')
    ->setParameter('name', $name);
```

### **2. Use Partial Objects for Performance**

```php
// Load only needed fields
$qb = $userRepository->createQueryBuilder('u')
    ->select('partial u.{id, name, email}')
    ->where('u.active = true');
```

### **3. Avoid N+1 Queries with JOIN FETCH**

❌ **Bad (N+1):**
```php
$courses = $courseRepository->findAll();
foreach ($courses as $course) {
    echo $course->getOwner()->getName(); // Extra query per course!
}
```

✅ **Good (Single Query):**
```php
$courses = $courseRepository->createQueryBuilder('c')
    ->leftJoin('c.owner', 'u')
    ->addSelect('u')
    ->getQuery()
    ->getResult();
```

### **4. Use Batch Processing for Large Datasets**

```php
$batchSize = 20;
$i = 0;

foreach ($users as $user) {
    $user->setActive(true);

    if (($i % $batchSize) === 0) {
        $entityManager->flush();
        $entityManager->clear(); // Prevent memory leaks
    }

    $i++;
}

$entityManager->flush(); // Final flush
```

### **5. Use Doctrine Filters for Multi-Tenancy**

```php
// Enable organization filter (automatic in Luminai)
$filter = $entityManager->getFilters()->enable('organization_filter');
$filter->setParameter('organization_id', $organizationId, 'string');

// All queries now automatically filtered:
$users = $userRepository->findAll(); // Only users from active organization
```

---

## Troubleshooting

### **UUIDv7 Not Generating**

```bash
# Check UuidV7Generator is registered
php bin/console doctrine:mapping:info

# Verify PostgreSQL supports uuid-ossp extension
docker-compose exec database psql -U luminai_user -d luminai_db -c "
SELECT * FROM pg_extension WHERE extname = 'uuid-ossp';"

# If not installed:
docker-compose exec database psql -U luminai_user -d luminai_db -c "
CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";"
```

### **Soft Delete Not Working**

```bash
# Check entity has trait
grep -r "SoftDeletableTrait" src/Entity/

# Verify subscriber is registered
php bin/console debug:event-dispatcher doctrine.event.preRemove

# Check logs
docker-compose exec app tail -f var/log/app.log | grep -i "soft.*delete"
```

### **Audit Trait Not Populating**

```bash
# Verify AuditSubscriber is registered
php bin/console debug:event-dispatcher doctrine.event.prePersist
php bin/console debug:event-dispatcher doctrine.event.preUpdate

# Check if user is authenticated
# AuditSubscriber handles CLI/unauthenticated contexts gracefully

# View audit logs
docker-compose exec app tail -f var/log/audit.log | jq .
```

### **Custom DQL Functions Not Working**

```bash
# Check configuration
php bin/console debug:config doctrine orm dql

# Verify function class exists
ls -la src/Doctrine/DQL/

# Test in DQL query
php bin/console doctrine:query:dql "SELECT UNACCENT(u.name) FROM App\Entity\User u"
```

### **Migration Errors**

```bash
# Check migration status
php bin/console doctrine:migrations:status

# View executed migrations
php bin/console doctrine:migrations:list

# Rollback last migration
php bin/console doctrine:migrations:migrate prev

# Force mark as executed (dangerous!)
php bin/console doctrine:migrations:version --add Version20250105120000
```

---

## Quick Reference

### **Creating a New Entity with All Features**

```php
<?php
namespace App\Entity;

use App\Entity\Trait\AuditTrait;
use App\Entity\Trait\SoftDeletableTrait;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MyEntityRepository::class)]
class MyEntity extends EntityBase
{
    use AuditTrait;
    use SoftDeletableTrait;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Organization $organization;

    public function __construct()
    {
        parent::__construct();
        $this->initializeAuditFields();
    }

    // Getters and setters...
}
```

**Features:**
✅ UUIDv7 primary key
✅ Automatic timestamps (createdAt, updatedAt)
✅ User tracking (createdBy, updatedBy)
✅ Soft delete support
✅ Organization scoped
✅ Complete audit trail

---

**For more information:**
- [Multi-Tenant Architecture](MULTI_TENANT.md)
- [Audit & Compliance System](AUDIT_SYSTEM.md)
- [Development Workflows](DEVELOPMENT.md)
