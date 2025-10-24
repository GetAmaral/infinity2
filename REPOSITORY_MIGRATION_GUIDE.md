# Repository Migration Guide

## Summary of Changes

### 1. BaseRepository Moved ✅
- **From**: `/app/src/Repository/BaseRepository.php`
- **To**: `/app/src/Repository/Base/BaseRepository.php`
- **Namespace**: `App\Repository\Base`

### 2. Templates Updated ✅
- **File**: `/app/templates/genmax/php/repository_generated.php.twig`
- **Change**: Updated import to `use App\Repository\Base\BaseRepository;`

### 3. Controllers Updated ✅
- **File**: `/app/src/Controller/BaseApiController.php`
- **Change**: Updated import to `use App\Repository\Base\BaseRepository;`

---

## Current Repository Structure

```
src/Repository/
├── Base/
│   └── BaseRepository.php                    ← CORE (moved here)
├── Generated/
│   ├── AgentRepositoryGenerated.php          ← AUTO-GENERATED (correct)
│   ├── CourseRepositoryGenerated.php         ← AUTO-GENERATED (correct)
│   └── ... (73 other *RepositoryGenerated.php files)
├── Generator/
│   ├── GeneratorEntityRepository.php         ← GenMax system (keep)
│   └── GeneratorPropertyRepository.php       ← GenMax system (keep)
└── *Repository.php                            ← OLD EXTENSIONS (to be deleted & regenerated)
```

---

## Action Required: Delete Old Repository Files

### Step 1: Backup (Optional but Recommended)

```bash
# Create backup of repositories with custom methods
docker-compose exec -T app tar -czf /tmp/old_repositories_backup.tar.gz /app/src/Repository/*.php
```

### Step 2: Delete All Old Repository Extensions

**IMPORTANT**: Only delete files in the ROOT of `/src/Repository/`, NOT in subfolders!

```bash
# Delete all old repository extension files (SAFE - excludes Generator/ folder)
docker-compose exec -T app sh -c 'rm -f /app/src/Repository/*.php'
```

**What this does**:
- ✅ Deletes: `AgentRepository.php`, `CourseRepository.php`, etc. (old pattern)
- ✅ Keeps: `Base/BaseRepository.php` (core)
- ✅ Keeps: `Generated/*RepositoryGenerated.php` (auto-generated bases)
- ✅ Keeps: `Generator/*Repository.php` (GenMax system)

### Step 3: Regenerate All Repositories

```bash
# Get list of all entities from database
docker-compose exec -T app php bin/console doctrine:query:sql "SELECT entity_name FROM generator_entity ORDER BY entity_name" --no-ansi | tail -n +4 | head -n -1 | tr -d ' ' > /tmp/entities.txt

# Generate all entities (this will create proper repository extensions)
docker-compose exec -T app php bin/console genmax:generate --all
```

**OR regenerate individually** if you want to check each one:

```bash
# Example for specific entities
docker-compose exec -T app php bin/console genmax:generate Agent
docker-compose exec -T app php bin/console genmax:generate Course
docker-compose exec -T app php bin/console genmax:generate Deal
# ... etc
```

### Step 4: Verify New Structure

After regeneration, each entity should have:

```
src/Repository/
├── Generated/
│   └── AgentRepositoryGenerated.php          ← Extends BaseRepository
└── AgentRepository.php                        ← Extends AgentRepositoryGenerated (with constructor)
```

**Verify one**:
```bash
docker-compose exec -T app cat /app/src/Repository/AgentRepository.php
```

Should see:
```php
<?php
namespace App\Repository;

use App\Entity\Agent;
use App\Repository\Generated\AgentRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

final class AgentRepository extends AgentRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Agent::class);
    }

    // Add custom query methods here
}
```

### Step 5: Clear Cache & Test

```bash
# Clear Symfony cache
docker-compose exec -T app php bin/console cache:clear

# Test migration creation (should work now)
docker-compose exec -T app php bin/console doctrine:migrations:diff

# If successful, migrate
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
```

---

## Repositories with Custom Methods

### Identified Repositories (Review Before Deleting)

Based on analysis, these repositories MAY have custom query methods:

1. **CourseRepository** - Has `findWithModulesAndLectures()`
2. **UserRepository** - Likely has authentication-related queries
3. **DealRepository** - Possible custom business logic
4. **OrganizationRepository** - Tenant-related queries

**⚠️ IMPORTANT**: Before deleting, check each file for custom methods:

```bash
# Find repositories with custom public methods (excluding getters/setters)
docker-compose exec -T app grep -l "public function find" /app/src/Repository/*.php 2>/dev/null
```

### Preserving Custom Methods

If you find custom methods you want to keep:

1. **Copy the method code** to a text file
2. **Delete the old repository**
3. **Regenerate** with GenMax
4. **Paste the custom methods** back into the new extension file

**Example - CourseRepository custom method**:

```php
// OLD (before deletion)
final class CourseRepository extends BaseRepository
{
    public function findWithModulesAndLectures(string $id): ?Course
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.modules', 'm')
            ->leftJoin('m.lectures', 'l')
            ->addSelect('m', 'l')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

// NEW (after regeneration, paste custom method)
final class CourseRepository extends CourseRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    // 👇 PASTE YOUR CUSTOM METHOD HERE
    public function findWithModulesAndLectures(string $id): ?Course
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.modules', 'm')
            ->leftJoin('m.lectures', 'l')
            ->addSelect('m', 'l')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
```

---

## New Repository Pattern Benefits

### Before (Old Pattern)
```php
// ❌ Directly extends BaseRepository
// ❌ No constructor
// ❌ Can't be regenerated without losing custom methods

class CourseRepository extends BaseRepository
{
    // All methods here (both generated and custom)
}
```

### After (New Pattern)
```php
// ✅ Generated base with field mappings
abstract class CourseRepositoryGenerated extends BaseRepository
{
    protected function getSearchableFields(): array { /* auto-generated */ }
    protected function getSortableFields(): array { /* auto-generated */ }
    // ... all auto-generated configuration
}

// ✅ Safe extension for custom methods
final class CourseRepository extends CourseRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Course::class);
    }

    // Add custom methods here - SAFE from regeneration!
}
```

**Benefits**:
- ✅ Field mappings auto-updated when entity changes
- ✅ Custom methods preserved in extension
- ✅ Proper Symfony autowiring with constructor
- ✅ Clear separation: generated vs custom code

---

## Troubleshooting

### Error: "Cannot autowire service"
**Cause**: Old repository without constructor
**Fix**: Delete and regenerate the repository

### Error: "Class not found"
**Cause**: Namespace mismatch
**Fix**: Clear cache and verify file structure

### Custom Method Doesn't Work
**Cause**: Method relies on old BaseRepository location
**Fix**: Update any hardcoded namespaces in the method

---

## Verification Checklist

After migration, verify:

- [ ] BaseRepository moved to `/app/src/Repository/Base/`
- [ ] All old `*Repository.php` files deleted (root folder only)
- [ ] All entities regenerated with new pattern
- [ ] Each repository has proper constructor
- [ ] Migrations can be generated (`doctrine:migrations:diff`)
- [ ] Cache cleared
- [ ] Custom methods preserved (if any)
- [ ] Tests pass

---

## Questions?

If you encounter issues:
1. Check this guide first
2. Verify file structure matches expected pattern
3. Clear cache
4. Check Symfony logs: `docker-compose logs app | tail -100`
