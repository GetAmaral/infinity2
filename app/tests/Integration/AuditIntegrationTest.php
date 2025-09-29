<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Organization;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class AuditIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testAuditFieldsArePopulatedOnEntityCreation(): void
    {
        $organization = new Organization();
        $organization->setName('Test Audit Org');
        $organization->setDescription('Testing audit functionality');

        // Verify initial audit fields from constructor
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getUpdatedAt());
        $this->assertNull($organization->getCreatedBy()); // No authenticated user in test
        $this->assertNull($organization->getUpdatedBy()); // No authenticated user in test

        $beforeCreation = new \DateTimeImmutable();

        $this->entityManager->persist($organization);
        $this->entityManager->flush();

        $afterCreation = new \DateTimeImmutable();

        // Verify timestamps are within reasonable range
        $this->assertGreaterThanOrEqual($beforeCreation->getTimestamp() - 1, $organization->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($afterCreation->getTimestamp() + 1, $organization->getCreatedAt()->getTimestamp());

        // CreatedAt and UpdatedAt should be the same on creation
        $this->assertEquals($organization->getCreatedAt()->getTimestamp(), $organization->getUpdatedAt()->getTimestamp());
    }

    public function testAuditFieldsAreUpdatedOnEntityModification(): void
    {
        // Create entity
        $organization = new Organization();
        $organization->setName('Original Name');

        $this->entityManager->persist($organization);
        $this->entityManager->flush();

        $originalCreatedAt = $organization->getCreatedAt();
        $originalUpdatedAt = $organization->getUpdatedAt();

        // Wait a moment to ensure different timestamp
        usleep(1000);

        // Update entity
        $organization->setName('Updated Name');
        $this->entityManager->flush();

        // Verify audit fields
        $this->assertEquals($originalCreatedAt, $organization->getCreatedAt()); // CreatedAt should not change
        $this->assertGreaterThan($originalUpdatedAt, $organization->getUpdatedAt()); // UpdatedAt should change
        $this->assertNull($organization->getCreatedBy()); // Still no authenticated user
        $this->assertNull($organization->getUpdatedBy()); // Still no authenticated user
    }

    public function testMultipleEntitiesHaveIndependentAuditFields(): void
    {
        $org1 = new Organization();
        $org1->setName('Organization 1');

        $org2 = new Organization();
        $org2->setName('Organization 2');

        $this->entityManager->persist($org1);
        $this->entityManager->persist($org2);
        $this->entityManager->flush();

        // Each entity should have its own audit timestamps
        $this->assertInstanceOf(\DateTimeImmutable::class, $org1->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $org2->getCreatedAt());

        // Timestamps might be very close but should be independent
        $this->assertNotSame($org1->getCreatedAt(), $org2->getCreatedAt());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}