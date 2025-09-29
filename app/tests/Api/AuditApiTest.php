<?php

declare(strict_types=1);

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Test suite for API audit field visibility and behavior
 *
 * Tests serialization groups and audit field exposure in API responses
 */
class AuditApiTest extends ApiTestCase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
    }

    public function testOrganizationApiHidesAuditFieldsByDefault(): void
    {
        // Create test organization
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setDescription('Test Description');

        $this->entityManager->persist($organization);
        $this->entityManager->flush();

        // Make API request to regular endpoint
        $response = static::createClient()->request('GET', '/api/organizations/' . $organization->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/ld+json; charset=utf-8');

        $data = $response->toArray();

        // Assert regular fields are present
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('description', $data);

        // Assert audit fields are NOT present in regular API response
        $this->assertArrayNotHasKey('createdAt', $data);
        $this->assertArrayNotHasKey('updatedAt', $data);
        $this->assertArrayNotHasKey('createdBy', $data);
        $this->assertArrayNotHasKey('updatedBy', $data);
    }

    public function testAdminOrganizationApiExposesAuditFields(): void
    {
        // Create admin user for authentication
        $adminUser = new User();
        $adminUser->setName('Admin User');
        $adminUser->setEmail('admin@example.com');
        $adminUser->setPassword('hashed_password');

        // Create test organization
        $organization = new Organization();
        $organization->setName('Test Organization');
        $organization->setDescription('Test Description');
        $organization->setCreatedBy($adminUser);
        $organization->setUpdatedBy($adminUser);

        $this->entityManager->persist($adminUser);
        $this->entityManager->persist($organization);
        $this->entityManager->flush();

        // Make API request to admin endpoint (mocking admin authentication)
        $client = static::createClient();

        // Note: In a real test, you would need to authenticate as admin
        // This is a simplified test focusing on serialization groups
        $response = $client->request('GET', '/api/admin/organizations', [
            'headers' => [
                'Accept' => 'application/ld+json',
                // Add authentication headers in real implementation
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Assert that collection contains organizations
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertCount(1, $data['hydra:member']);

        $organizationData = $data['hydra:member'][0];

        // Assert regular fields are present
        $this->assertArrayHasKey('name', $organizationData);
        $this->assertArrayHasKey('description', $organizationData);

        // Assert audit fields ARE present in admin API response
        $this->assertArrayHasKey('createdAt', $organizationData);
        $this->assertArrayHasKey('updatedAt', $organizationData);
        $this->assertArrayHasKey('createdBy', $organizationData);
        $this->assertArrayHasKey('updatedBy', $organizationData);
    }

    public function testUserApiHidesAuditFieldsByDefault(): void
    {
        // Create test user
        $user = new User();
        $user->setName('Test User');
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Make API request to regular user endpoint
        $response = static::createClient()->request('GET', '/api/users/' . $user->getId());

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Assert regular fields are present
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);

        // Assert sensitive fields are not present
        $this->assertArrayNotHasKey('password', $data);

        // Assert audit fields are NOT present in regular API response
        $this->assertArrayNotHasKey('createdAt', $data);
        $this->assertArrayNotHasKey('updatedAt', $data);
        $this->assertArrayNotHasKey('createdBy', $data);
        $this->assertArrayNotHasKey('updatedBy', $data);
    }

    public function testAdminUserApiExposesAuditFields(): void
    {
        // Create admin user for authentication
        $adminUser = new User();
        $adminUser->setName('Admin User');
        $adminUser->setEmail('admin@example.com');
        $adminUser->setPassword('hashed_password');

        // Create test user
        $testUser = new User();
        $testUser->setName('Test User');
        $testUser->setEmail('test@example.com');
        $testUser->setPassword('hashed_password');
        $testUser->setCreatedBy($adminUser);
        $testUser->setUpdatedBy($adminUser);

        $this->entityManager->persist($adminUser);
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        // Make API request to admin users endpoint
        $client = static::createClient();

        $response = $client->request('GET', '/api/admin/users', [
            'headers' => [
                'Accept' => 'application/ld+json',
                // Add authentication headers in real implementation
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Assert that collection contains users
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertGreaterThanOrEqual(1, count($data['hydra:member']));

        // Find our test user in the response
        $testUserData = null;
        foreach ($data['hydra:member'] as $userData) {
            if ($userData['email'] === 'test@example.com') {
                $testUserData = $userData;
                break;
            }
        }

        $this->assertNotNull($testUserData, 'Test user not found in API response');

        // Assert regular fields are present
        $this->assertArrayHasKey('name', $testUserData);
        $this->assertArrayHasKey('email', $testUserData);

        // Assert audit fields ARE present in admin API response
        $this->assertArrayHasKey('createdAt', $testUserData);
        $this->assertArrayHasKey('updatedAt', $testUserData);
        $this->assertArrayHasKey('createdBy', $testUserData);
        $this->assertArrayHasKey('updatedBy', $testUserData);
    }

    public function testAuditFieldsContainValidData(): void
    {
        // Create users
        $adminUser = new User();
        $adminUser->setName('Admin User');
        $adminUser->setEmail('admin@example.com');
        $adminUser->setPassword('hashed_password');

        $testUser = new User();
        $testUser->setName('Test User');
        $testUser->setEmail('test@example.com');
        $testUser->setPassword('hashed_password');
        $testUser->setCreatedBy($adminUser);
        $testUser->setUpdatedBy($adminUser);

        $this->entityManager->persist($adminUser);
        $this->entityManager->persist($testUser);
        $this->entityManager->flush();

        // Make API request to admin endpoint
        $response = static::createClient()->request('GET', '/api/admin/users');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();

        // Find test user data
        $testUserData = null;
        foreach ($data['hydra:member'] as $userData) {
            if ($userData['email'] === 'test@example.com') {
                $testUserData = $userData;
                break;
            }
        }

        $this->assertNotNull($testUserData);

        // Assert audit timestamp format
        $this->assertIsString($testUserData['createdAt']);
        $this->assertIsString($testUserData['updatedAt']);

        // Assert timestamps are valid ISO 8601 format
        $this->assertNotFalse(\DateTime::createFromFormat(\DateTime::ATOM, $testUserData['createdAt']));
        $this->assertNotFalse(\DateTime::createFromFormat(\DateTime::ATOM, $testUserData['updatedAt']));

        // Assert user references contain IRI links
        $this->assertIsString($testUserData['createdBy']);
        $this->assertIsString($testUserData['updatedBy']);
        $this->assertStringContains('/api/users/', $testUserData['createdBy']);
        $this->assertStringContains('/api/users/', $testUserData['updatedBy']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up test data
        $this->entityManager->createQuery('DELETE FROM App\Entity\User')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\Organization')->execute();
    }
}