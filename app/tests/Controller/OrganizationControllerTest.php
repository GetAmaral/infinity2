<?php

namespace App\Tests\Controller;

use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrganizationControllerTest extends WebTestCase
{
    /**
     */
    public function testOrganizationIndexPageLoads(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('title', 'Organizations');
        $this->assertSelectorTextContains('h1', 'Organizations');
        $this->assertSelectorExists('a[href="/api/organizations"]');
    }

    /**
     */
    public function testOrganizationIndexWithData(): void
    {
        $client = static::createClient();

        // Create test organization
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $organization = new Organization();
        $organization->setName('Unique Test Org ' . time());
        $organization->setDescription('Unique test description ' . time());
        $entityManager->persist($organization);
        $entityManager->flush();

        $crawler = $client->request('GET', '/organization');

        $this->assertResponseIsSuccessful();
        // Check that organization was created and appears on the page
        $this->assertStringContainsString($organization->getName(), $client->getResponse()->getContent());
        $this->assertStringContainsString($organization->getDescription(), $client->getResponse()->getContent());

        // Clean up - clear the entity manager
        $entityManager->clear();
    }

    /**
     */
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
        $this->assertStringContainsString($organization->getDescription(), $client->getResponse()->getContent());
        $this->assertSelectorExists('a[href="/organization"]');

        // Clean up - clear the entity manager
        $entityManager->clear();
    }
}