<?php

namespace App\Tests\Entity;

use App\Entity\Organization;
use PHPUnit\Framework\TestCase;

class OrganizationTest extends TestCase
{
    public function testOrganizationCreation(): void
    {
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setDescription('A test organization for unit testing');

        $this->assertEquals('Test Organization', $organization->getName());
        $this->assertEquals('A test organization for unit testing', $organization->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $organization->getUpdatedAt());
    }

    public function testPreUpdate(): void
    {
        $organization = new Organization();
        $originalUpdatedAt = $organization->getUpdatedAt();

        // Simulate time passing
        usleep(1000); // 1ms

        $organization->preUpdate();
        $newUpdatedAt = $organization->getUpdatedAt();

        $this->assertGreaterThan($originalUpdatedAt, $newUpdatedAt);
    }

    public function testUserCollection(): void
    {
        $organization = new Organization();

        $this->assertCount(0, $organization->getUsers());
        $this->assertEquals([], $organization->getUsers()->toArray());
    }
}