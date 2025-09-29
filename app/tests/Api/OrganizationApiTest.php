<?php

namespace App\Tests\Api;

use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class OrganizationApiTest extends WebTestCase
{
    public function testGetOrganizationsApi(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/organizations');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('@context', $content);
        $this->assertArrayHasKey('@type', $content);
        $this->assertArrayHasKey('member', $content);
        $this->assertArrayHasKey('totalItems', $content);
    }

    public function testGetSingleOrganizationApi(): void
    {
        $client = static::createClient();

        // Create test organization
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $organization = new Organization();
        $organization->setName('API Test Organization');
        $organization->setDescription('Organization for API testing');
        $entityManager->persist($organization);
        $entityManager->flush();

        $client->request('GET', '/api/organizations/' . $organization->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $this->assertEquals('API Test Organization', $content['name']);
        $this->assertEquals('Organization for API testing', $content['description']);
        $this->assertArrayHasKey('createdAt', $content);
        $this->assertArrayHasKey('updatedAt', $content);

        // Clean up
        $entityManager->remove($organization);
        $entityManager->flush();
    }

    public function testCreateOrganizationApi(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/organizations',
            [], // parameters
            [], // files
            ['CONTENT_TYPE' => 'application/ld+json'], // server
            json_encode([
                'name' => 'Created via API',
                'description' => 'This organization was created through the API'
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('Created via API', $content['name']);
        $this->assertEquals('This organization was created through the API', $content['description']);

        // Clean up - get the organization by name and remove it
        $entityManager = $client->getContainer()->get(EntityManagerInterface::class);
        $organization = $entityManager->getRepository(Organization::class)
            ->findOneBy(['name' => 'Created via API']);

        if ($organization) {
            $entityManager->remove($organization);
            $entityManager->flush();
        }
    }
}