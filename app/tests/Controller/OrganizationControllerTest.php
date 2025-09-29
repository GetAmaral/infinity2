<?php

namespace App\Tests\Controller;

use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrganizationControllerTest extends WebTestCase
{
    public function testOrganizationIndexPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Organizations');
        $this->assertSelectorTextContains('h1', 'Organizations');
        $this->assertSelectorExists('a[href="/api/organizations"]');
    }

    public function testOrganizationIndexWithData(): void
    {
        $client = static::createClient();

        // Create test organization
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setDescription('A test organization');
        $entityManager->persist($organization);
        $entityManager->flush();

        $crawler = $client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.infinity-card', 'Test Organization');
        $this->assertSelectorTextContains('.infinity-card', 'A test organization');

        // Clean up
        $entityManager->remove($organization);
        $entityManager->flush();
    }

    public function testOrganizationShowPage(): void
    {
        $client = static::createClient();

        // Create test organization
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $organization = new Organization();
        $organization->setName('Show Test Organization');
        $organization->setDescription('Organization for show page test');
        $entityManager->persist($organization);
        $entityManager->flush();

        $crawler = $client->request('GET', '/organization/' . $organization->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Show Test Organization');
        $this->assertSelectorTextContains('h1', 'Show Test Organization');
        $this->assertSelectorTextContains('.infinity-card', 'Organization for show page test');
        $this->assertSelectorExists('a[href="/organization"]');

        // Clean up
        $entityManager->remove($organization);
        $entityManager->flush();
    }
}