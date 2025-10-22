<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Profile;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * ProfileRepository
 *
 * Custom repository methods for Profile entity with optimized queries.
 *
 * @extends ServiceEntityRepository<Profile>
 *
 * @method Profile|null find($id, $lockMode = null, $lockVersion = null)
 * @method Profile|null findOneBy(array $criteria, array $orderBy = null)
 * @method Profile[]    findAll()
 * @method Profile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profile::class);
    }

    /**
     * Save profile entity
     */
    public function save(Profile $profile, bool $flush = true): void
    {
        $this->getEntityManager()->persist($profile);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove profile entity
     */
    public function remove(Profile $profile, bool $flush = true): void
    {
        $this->getEntityManager()->remove($profile);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find profile by user
     */
    public function findByUser(User $user): ?Profile
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :user')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find profile by user ID
     */
    public function findByUserId(Uuid $userId): ?Profile
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.user = :userId')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all active profiles for an organization
     *
     * @return Profile[]
     */
    public function findActiveByOrganization(Organization $organization): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all public profiles
     *
     * @return Profile[]
     */
    public function findPublicProfiles(int $limit = 100): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.public = :public')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('public', true)
            ->setParameter('active', true)
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find profiles by department
     *
     * @return Profile[]
     */
    public function findByDepartment(Organization $organization, string $department): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.department = :department')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('department', $department)
            ->setParameter('active', true)
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find profiles by sales team
     *
     * @return Profile[]
     */
    public function findBySalesTeam(Organization $organization, string $salesTeam): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.salesTeam = :salesTeam')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('salesTeam', $salesTeam)
            ->setParameter('active', true)
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find profiles by location (city, state, country)
     *
     * @return Profile[]
     */
    public function findByLocation(
        Organization $organization,
        ?string $city = null,
        ?string $state = null,
        ?string $country = null
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true);

        if ($city !== null) {
            $qb->andWhere('p.city = :city')
                ->setParameter('city', $city);
        }

        if ($state !== null) {
            $qb->andWhere('p.state = :state')
                ->setParameter('state', $state);
        }

        if ($country !== null) {
            $qb->andWhere('p.country = :country')
                ->setParameter('country', $country);
        }

        return $qb
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search profiles by name
     *
     * @return Profile[]
     */
    public function searchByName(Organization $organization, string $searchTerm): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('
                p.firstName LIKE :search
                OR p.lastName LIKE :search
                OR p.displayName LIKE :search
                OR CONCAT(p.firstName, \' \', p.lastName) LIKE :search
            ')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find profiles with sales targets
     *
     * @return Profile[]
     */
    public function findProfilesWithSalesTargets(Organization $organization): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.salesTarget IS NOT NULL')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('p.salesAchieved', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get sales leaderboard for organization
     *
     * Returns profiles ordered by sales achievement percentage
     *
     * @return Profile[]
     */
    public function getSalesLeaderboard(Organization $organization, int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.salesTarget IS NOT NULL')
            ->andWhere('p.salesAchieved IS NOT NULL')
            ->andWhere('p.salesTarget > 0')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('(p.salesAchieved / p.salesTarget)', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find verified profiles
     *
     * @return Profile[]
     */
    public function findVerified(Organization $organization): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.verified = :verified')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('verified', true)
            ->setParameter('active', true)
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Count profiles by department
     *
     * @return array<string, int> Department name => count
     */
    public function countByDepartment(Organization $organization): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('p.department, COUNT(p.id) as profileCount')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.department IS NOT NULL')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->groupBy('p.department')
            ->orderBy('profileCount', 'DESC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['department']] = (int) $result['profileCount'];
        }

        return $counts;
    }

    /**
     * Count profiles by location
     *
     * @return array<string, int> Location => count
     */
    public function countByLocation(Organization $organization): array
    {
        $results = $this->createQueryBuilder('p')
            ->select('p.city, p.state, p.country, COUNT(p.id) as profileCount')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->groupBy('p.city, p.state, p.country')
            ->orderBy('profileCount', 'DESC')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($results as $result) {
            $location = trim(implode(', ', array_filter([
                $result['city'],
                $result['state'],
                $result['country']
            ])));
            if ($location) {
                $counts[$location] = (int) $result['profileCount'];
            }
        }

        return $counts;
    }

    /**
     * Get aggregate sales statistics for organization
     *
     * @return array{totalTarget: float, totalAchieved: float, avgAchievementRate: float, profileCount: int}
     */
    public function getSalesStatistics(Organization $organization): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('
                SUM(p.salesTarget) as totalTarget,
                SUM(p.salesAchieved) as totalAchieved,
                COUNT(p.id) as profileCount
            ')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.salesTarget IS NOT NULL')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleResult();

        $totalTarget = (float) ($result['totalTarget'] ?? 0);
        $totalAchieved = (float) ($result['totalAchieved'] ?? 0);
        $profileCount = (int) ($result['profileCount'] ?? 0);

        $avgAchievementRate = 0.0;
        if ($totalTarget > 0) {
            $avgAchievementRate = round(($totalAchieved / $totalTarget) * 100, 2);
        }

        return [
            'totalTarget' => $totalTarget,
            'totalAchieved' => $totalAchieved,
            'avgAchievementRate' => $avgAchievementRate,
            'profileCount' => $profileCount,
        ];
    }

    /**
     * Soft delete a profile
     */
    public function softDelete(Profile $profile, bool $flush = true): void
    {
        $profile->setDeletedAt(new \DateTimeImmutable());
        $this->save($profile, $flush);
    }

    /**
     * Restore a soft-deleted profile
     */
    public function restore(Profile $profile, bool $flush = true): void
    {
        $profile->setDeletedAt(null);
        $this->save($profile, $flush);
    }

    /**
     * Find profiles with missing required fields (for data quality reports)
     *
     * @return Profile[]
     */
    public function findIncompleteProfiles(Organization $organization): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.active = :active')
            ->andWhere('p.deletedAt IS NULL')
            ->andWhere('
                p.phone IS NULL
                OR p.jobTitle IS NULL
                OR p.department IS NULL
                OR p.timezone IS NULL
            ')
            ->setParameter('organization', $organization)
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
