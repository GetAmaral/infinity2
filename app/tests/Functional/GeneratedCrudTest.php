<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Service\Generator\Csv\CsvParserService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional test for generated CRUD operations
 *
 * Tests complete CRUD workflows for generated entities
 */
class GeneratedCrudTest extends WebTestCase
{
    private CsvParserService $parser;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $kernel = self::bootKernel();
        $container = static::getContainer();

        $this->parser = $container->get(CsvParserService::class);
    }

    public function testIndexActionLoads(): void
    {
        $client = static::createClient();
        $result = $this->parser->parseAll();
        $entities = $result['entities'];

        if (empty($entities)) {
            $this->markTestSkipped('No entities in CSV');
        }

        // Test first entity index page
        $entity = $entities[0];
        $entityLower = strtolower($entity['entityName']);
        $url = '/' . $entityLower;

        $crawler = $client->request('GET', $url);

        // Should load successfully or redirect to login
        $this->assertResponseIsSuccessful();
    }

    public function testSearchWorks(): void
    {
        $client = static::createClient();
        $result = $this->parser->parseAll();
        $entities = $result['entities'];

        if (empty($entities)) {
            $this->markTestSkipped('No entities in CSV');
        }

        // Find entity with searchable fields
        $searchableEntity = null;
        foreach ($entities as $entity) {
            if (!empty($entity['searchableFields'])) {
                $searchableEntity = $entity;
                break;
            }
        }

        if (!$searchableEntity) {
            $this->markTestSkipped('No entities with searchable fields');
        }

        $entityLower = strtolower($searchableEntity['entityName']);
        $url = '/' . $entityLower . '?search=test';

        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    public function testPaginationWorks(): void
    {
        $client = static::createClient();
        $result = $this->parser->parseAll();
        $entities = $result['entities'];

        if (empty($entities)) {
            $this->markTestSkipped('No entities in CSV');
        }

        $entity = $entities[0];
        $entityLower = strtolower($entity['entityName']);
        $url = '/' . $entityLower . '?page=1';

        $crawler = $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }
}
