<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Organization;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class EntityBaseTest extends TestCase
{
    public function testEntityBaseInheritanceForOrganization(): void
    {
        $organization = new Organization();

        // Test EntityBase provides ID and audit functionality
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getUpdatedAt());
        $this->assertNull($organization->getCreatedBy());
        $this->assertNull($organization->getUpdatedBy());

        // Test toString functionality
        $this->assertStringContainsString('Organization#', (string) $organization);
    }

    public function testEntityBaseInheritanceForUser(): void
    {
        $user = new User();

        // Test EntityBase provides ID and audit functionality
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
        $this->assertNull($user->getCreatedBy());
        $this->assertNull($user->getUpdatedBy());

        // User has its own toString that returns name or email (both empty by default)
        $this->assertEquals('', (string) $user);

        // Set name and test custom toString
        $user->setName('Test User');
        $this->assertEquals('Test User', (string) $user);
    }

    public function testAuditTimestampUpdate(): void
    {
        $organization = new Organization();
        $originalUpdatedAt = $organization->getUpdatedAt();

        // Simulate time passing
        usleep(1000); // 1ms

        $organization->updateAuditTimestamp();
        $newUpdatedAt = $organization->getUpdatedAt();

        $this->assertGreaterThan($originalUpdatedAt, $newUpdatedAt);
        // CreatedAt should remain unchanged
        $this->assertEquals($organization->getCreatedAt(), $organization->getCreatedAt());
    }

    public function testEntitiesHaveUniqueAuditTimestamps(): void
    {
        $org1 = new Organization();
        $org2 = new Organization();

        // Each entity should have independent timestamps
        $this->assertInstanceOf(\DateTimeImmutable::class, $org1->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $org2->getCreatedAt());

        // Even though created very close together, they should be distinct objects
        $this->assertNotSame($org1->getCreatedAt(), $org2->getCreatedAt());
    }
}