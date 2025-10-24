# Custom Repository Methods to Preserve

## ⚠️ IMPORTANT
These repositories have custom query methods that should be copied to the new generated repositories after regeneration.

---

## 1. AuditLogRepository

### Custom Methods:
```php
public function findByEntity(string $entityClass, Uuid $entityId): array
{
    return $this->createQueryBuilder('a')
        ->where('a.entityClass = :class')
        ->andWhere('a.entityId = :id')
        ->orderBy('a.createdAt', 'DESC')
        ->setParameter('class', $entityClass)
        ->setParameter('id', $entityId)
        ->getQuery()
        ->getResult();
}

public function findByUser(User $user, ?\DateTimeInterface $since = null): array
{
    $qb = $this->createQueryBuilder('a')
        ->where('a.user = :user')
        ->setParameter('user', $user);

    if ($since) {
        $qb->andWhere('a.createdAt >= :since')
           ->setParameter('since', $since);
    }

    return $qb->orderBy('a.createdAt', 'DESC')
        ->getQuery()
        ->getResult();
}
```

---

## 2. OrganizationRepository

### Custom Methods:
```php
public function findAllWithUserCounts(): array
{
    return $this->findAllWithRelations(['users']);
}

public function findMostActive(int $limit = 5): array
{
    return $this->createQueryBuilder('o')
        ->leftJoin('o.users', 'u')
        ->addSelect('u')
        ->groupBy('o.id')
        ->orderBy('COUNT(u.id)', 'DESC')
        ->setMaxResults($limit)
        ->getQuery()
        ->getResult();
}
```

---

## 3. RoleRepository

### Custom Methods:
```php
public function findNonSystemRoles(): array
{
    return $this->createQueryBuilder('r')
        ->andWhere('r.isSystem = :isSystem')
        ->setParameter('isSystem', false)
        ->orderBy('r.name', 'ASC')
        ->getQuery()
        ->getResult();
}

public function findOneByName(string $name): ?Role
{
    return $this->createQueryBuilder('r')
        ->andWhere('r.name = :name')
        ->setParameter('name', $name)
        ->getQuery()
        ->getOneOrNullResult();
}
```

---

## 4. UserRepository

### Custom Methods:
```php
public function findAllWithOrganization(): array
{
    return $this->findAllWithRelations(['organization']);
}

public function findByOrganization(int $organizationId): array
{
    return $this->createQueryBuilder('u')
        ->where('u.organization = :org')
        ->setParameter('org', $organizationId)
        ->orderBy('u.name', 'ASC')
        ->getQuery()
        ->getResult();
}
```

---

## Migration Workflow for These Repositories

### For Each Repository:

1. **Copy the custom methods** (listed above)
2. **Delete** the old repository file
3. **Regenerate** with GenMax:
   ```bash
   docker-compose exec -T app php bin/console genmax:generate AuditLog
   docker-compose exec -T app php bin/console genmax:generate Organization
   docker-compose exec -T app php bin/console genmax:generate Role
   docker-compose exec -T app php bin/console genmax:generate User
   ```
4. **Paste the custom methods** into the new repository extension file
5. **Add any missing use statements** (e.g., `use App\Entity\User;`)

### Example: UserRepository After Regeneration

```php
<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Repository\Generated\UserRepositoryGenerated;
use Doctrine\Persistence\ManagerRegistry;

final class UserRepository extends UserRepositoryGenerated
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // 👇 PASTE CUSTOM METHODS BELOW

    public function findAllWithOrganization(): array
    {
        return $this->findAllWithRelations(['organization']);
    }

    public function findByOrganization(int $organizationId): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.organization = :org')
            ->setParameter('org', $organizationId)
            ->orderBy('u.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

---

## Full Repository Files Backed Up

Complete backup files saved to:
- `/tmp/AuditLogRepository_BACKUP.php`
- `/tmp/OrganizationRepository_BACKUP.php`
- `/tmp/RoleRepository_BACKUP.php`
- `/tmp/UserRepository_BACKUP.php`

You can reference these files if you need to see the full context of the custom methods.
