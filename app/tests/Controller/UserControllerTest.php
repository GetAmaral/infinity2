<?php

namespace App\Tests\Controller;

use App\Entity\Organization;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testUserIndexPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/user');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Users');
        $this->assertSelectorTextContains('h1', 'Users');
        $this->assertSelectorExists('a[href="/api/users"]');
    }

    public function testUserIndexWithData(): void
    {
        $client = static::createClient();

        // Create test user with organization
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);

        $organization = new Organization();
        $organization->setName('User Test Org');
        $entityManager->persist($organization);

        $user = new User();
        $user->setName('John Doe');
        $user->setEmail('john.doe@test.com');
        $user->setOrganization($organization);
        $entityManager->persist($user);

        $entityManager->flush();

        $crawler = $client->request('GET', '/user');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.infinity-card', 'John Doe');
        $this->assertSelectorTextContains('.infinity-card', 'john.doe@test.com');
        $this->assertSelectorTextContains('.infinity-card', 'User Test Org');

        // Clean up
        $entityManager->remove($user);
        $entityManager->remove($organization);
        $entityManager->flush();
    }

    public function testUserShowPage(): void
    {
        $client = static::createClient();

        // Create test user
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);

        $organization = new Organization();
        $organization->setName('Show Test Org');
        $entityManager->persist($organization);

        $user = new User();
        $user->setName('Jane Smith');
        $user->setEmail('jane.smith@test.com');
        $user->setOrganization($organization);
        $entityManager->persist($user);

        $entityManager->flush();

        $crawler = $client->request('GET', '/user/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Jane Smith');
        $this->assertSelectorTextContains('h1', 'Jane Smith');
        $this->assertSelectorTextContains('.infinity-card', 'jane.smith@test.com');
        $this->assertSelectorTextContains('.infinity-card', 'Show Test Org');
        $this->assertSelectorExists('a[href="/user"]');

        // Clean up
        $entityManager->remove($user);
        $entityManager->remove($organization);
        $entityManager->flush();
    }
}