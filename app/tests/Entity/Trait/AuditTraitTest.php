<?php

declare(strict_types=1);

namespace App\Tests\Entity\Trait;

use App\Entity\User;
use App\Entity\Organization;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for AuditTrait functionality
 *
 * Tests audit field management and initialization behavior
 */
class AuditTraitTest extends TestCase
{
    public function testAuditFieldsAreInitializedOnConstruction(): void
    {
        $organization = new Organization();

        // Assert that audit timestamps are set during construction
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getUpdatedAt());

        // Assert that initial timestamps are the same
        $this->assertEquals($organization->getCreatedAt(), $organization->getUpdatedAt());

        // Assert that user references are null initially
        $this->assertNull($organization->getCreatedBy());
        $this->assertNull($organization->getUpdatedBy());
    }

    public function testAuditTimestampUpdate(): void
    {
        $organization = new Organization();
        $initialUpdatedAt = $organization->getUpdatedAt();

        // Simulate time passing
        usleep(1000); // 1ms delay to ensure timestamp difference

        // Update the audit timestamp
        $organization->updateAuditTimestamp();

        // Assert that updatedAt has changed
        $this->assertGreaterThan($initialUpdatedAt, $organization->getUpdatedAt());

        // Assert that createdAt remains unchanged
        $this->assertEquals($organization->getCreatedAt(), $initialUpdatedAt);
    }

    public function testSettersAndGetters(): void
    {
        $organization = new Organization();
        $user = new User();

        $createdAt = new \DateTimeImmutable('2024-01-01 10:00:00');
        $updatedAt = new \DateTimeImmutable('2024-01-02 11:00:00');

        // Test setters
        $organization->setCreatedAt($createdAt);
        $organization->setUpdatedAt($updatedAt);
        $organization->setCreatedBy($user);
        $organization->setUpdatedBy($user);

        // Test getters
        $this->assertEquals($createdAt, $organization->getCreatedAt());
        $this->assertEquals($updatedAt, $organization->getUpdatedAt());
        $this->assertSame($user, $organization->getCreatedBy());
        $this->assertSame($user, $organization->getUpdatedBy());
    }

    public function testSettersReturnSelf(): void
    {
        $organization = new Organization();
        $user = new User();
        $timestamp = new \DateTimeImmutable();

        // Test fluent interface
        $result = $organization
            ->setCreatedAt($timestamp)
            ->setUpdatedAt($timestamp)
            ->setCreatedBy($user)
            ->setUpdatedBy($user);

        $this->assertSame($organization, $result);
    }

    public function testAuditFieldsWithNullUsers(): void
    {
        $organization = new Organization();

        // Set user references to null
        $organization->setCreatedBy(null);
        $organization->setUpdatedBy(null);

        // Assert that null values are handled correctly
        $this->assertNull($organization->getCreatedBy());
        $this->assertNull($organization->getUpdatedBy());
    }

    public function testUserEntityUsesAuditTrait(): void
    {
        $user = new User();

        // Assert that User entity has audit functionality
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());

        // Test audit methods exist
        $this->assertTrue(method_exists($user, 'setCreatedBy'));
        $this->assertTrue(method_exists($user, 'setUpdatedBy'));
        $this->assertTrue(method_exists($user, 'updateAuditTimestamp'));
    }

    public function testOrganizationEntityUsesAuditTrait(): void
    {
        $organization = new Organization();

        // Assert that Organization entity has audit functionality
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getUpdatedAt());

        // Test audit methods exist
        $this->assertTrue(method_exists($organization, 'setCreatedBy'));
        $this->assertTrue(method_exists($organization, 'setUpdatedBy'));
        $this->assertTrue(method_exists($organization, 'updateAuditTimestamp'));
    }

    public function testAuditTimestampPrecision(): void
    {
        $organization1 = new Organization();
        usleep(1000); // 1ms delay
        $organization2 = new Organization();

        // Assert that timestamps have sufficient precision to detect differences
        $this->assertNotEquals(
            $organization1->getCreatedAt()->format('Y-m-d H:i:s.u'),
            $organization2->getCreatedAt()->format('Y-m-d H:i:s.u')
        );
    }

    public function testAuditFieldsAreImmutable(): void
    {
        $organization = new Organization();
        $originalCreatedAt = $organization->getCreatedAt();

        // Get the timestamp again
        $secondCreatedAt = $organization->getCreatedAt();

        // Assert that getting the same field returns the same immutable object
        $this->assertEquals($originalCreatedAt, $secondCreatedAt);

        // Assert that modifying returned objects doesn't affect the entity
        $modifiedTimestamp = $originalCreatedAt->modify('+1 day');
        $this->assertNotEquals($modifiedTimestamp, $organization->getCreatedAt());
    }
}