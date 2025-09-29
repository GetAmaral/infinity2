<?php

namespace App\Tests\Entity;

use App\Entity\Organization;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john.doe@example.com');

        $this->assertEquals('John Doe', $user->getName());
        $this->assertEquals('john.doe@example.com', $user->getEmail());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getUpdatedAt());
        $this->assertNull($user->getOrganization());
    }

    public function testUserOrganizationRelationship(): void
    {
        $organization = new Organization();
        $organization->setName('Test Org');

        $user = new User();
        $user->setName('Jane Smith');
        $user->setEmail('jane.smith@example.com');
        $user->setOrganization($organization);

        $this->assertEquals($organization, $user->getOrganization());
        $this->assertEquals('Test Org', $user->getOrganization()->getName());
    }

    public function testPreUpdate(): void
    {
        $user = new User();
        $originalUpdatedAt = $user->getUpdatedAt();

        // Simulate time passing
        usleep(1000); // 1ms

        $user->preUpdate();
        $newUpdatedAt = $user->getUpdatedAt();

        $this->assertGreaterThan($originalUpdatedAt, $newUpdatedAt);
    }
}