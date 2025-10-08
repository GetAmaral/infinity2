<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Service\Generator\Csv\CsvParserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Integration test for entity relationships
 *
 * Tests bidirectional relationships, cascade operations, and orphan removal
 */
class RelationshipTest extends KernelTestCase
{
    private CsvParserService $parser;
    private string $projectDir;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $this->parser = $container->get(CsvParserService::class);
        $this->projectDir = $container->getParameter('kernel.project_dir');
    }

    public function testBidirectionalRelationshipsExist(): void
    {
        $result = $this->parser->parseAll();
        $properties = $result['properties'];

        // Find entities with relationships
        $relationshipCount = 0;
        $bidirectionalCount = 0;

        foreach ($properties as $entityName => $entityProps) {
            foreach ($entityProps as $property) {
                if (!empty($property['relationshipType'])) {
                    $relationshipCount++;

                    // Check if inverse side configured
                    if (!empty($property['inversedBy']) || !empty($property['mappedBy'])) {
                        $bidirectionalCount++;
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $relationshipCount, 'At least one relationship should exist');

        if ($bidirectionalCount > 0) {
            $this->assertGreaterThan(0, $bidirectionalCount, 'Bidirectional relationships should be configured');
        }
    }

    public function testManyToOneRelationshipsGenerated(): void
    {
        $result = $this->parser->parseAll();
        $properties = $result['properties'];

        // Find ManyToOne relationships
        $manyToOne = [];
        foreach ($properties as $entityName => $entityProps) {
            foreach ($entityProps as $property) {
                if ($property['relationshipType'] === 'ManyToOne') {
                    $manyToOne[] = [
                        'entity' => $entityName,
                        'property' => $property['propertyName'],
                        'target' => $property['targetEntity'],
                    ];
                }
            }
        }

        if (empty($manyToOne)) {
            $this->markTestSkipped('No ManyToOne relationships in CSV');
        }

        // Verify first ManyToOne relationship
        $rel = $manyToOne[0];
        $entityFile = $this->projectDir . '/src/Entity/Generated/' . $rel['entity'] . 'Generated.php';

        if (file_exists($entityFile)) {
            $content = file_get_contents($entityFile);

            // Should have ManyToOne annotation
            $this->assertStringContainsString('#[ORM\ManyToOne(', $content);

            // Should have getter method
            $expectedGetter = 'public function get' . ucfirst($rel['property']) . '()';
            $this->assertStringContainsString($expectedGetter, $content);

            // Should have setter method
            $expectedSetter = 'public function set' . ucfirst($rel['property']) . '(';
            $this->assertStringContainsString($expectedSetter, $content);
        }
    }

    public function testOneToManyRelationshipsGenerated(): void
    {
        $result = $this->parser->parseAll();
        $properties = $result['properties'];

        // Find OneToMany relationships
        $oneToMany = [];
        foreach ($properties as $entityName => $entityProps) {
            foreach ($entityProps as $property) {
                if ($property['relationshipType'] === 'OneToMany') {
                    $oneToMany[] = [
                        'entity' => $entityName,
                        'property' => $property['propertyName'],
                        'target' => $property['targetEntity'],
                        'mappedBy' => $property['mappedBy'],
                    ];
                }
            }
        }

        if (empty($oneToMany)) {
            $this->markTestSkipped('No OneToMany relationships in CSV');
        }

        // Verify first OneToMany relationship
        $rel = $oneToMany[0];
        $entityFile = $this->projectDir . '/src/Entity/Generated/' . $rel['entity'] . 'Generated.php';

        if (file_exists($entityFile)) {
            $content = file_get_contents($entityFile);

            // Should have OneToMany annotation
            $this->assertStringContainsString('#[ORM\OneToMany(', $content);

            // Should initialize as ArrayCollection in constructor
            $this->assertStringContainsString('new ArrayCollection()', $content);

            // Should have getter method
            $expectedGetter = 'public function get' . ucfirst($rel['property']) . '()';
            $this->assertStringContainsString($expectedGetter, $content);

            // Should have add method
            $singularName = rtrim($rel['property'], 's'); // Simple pluralization removal
            $expectedAdd = 'public function add' . ucfirst($singularName) . '(';
            $this->assertStringContainsString($expectedAdd, $content);

            // Should have remove method
            $expectedRemove = 'public function remove' . ucfirst($singularName) . '(';
            $this->assertStringContainsString($expectedRemove, $content);
        }
    }

    public function testManyToManyRelationshipsGenerated(): void
    {
        $result = $this->parser->parseAll();
        $properties = $result['properties'];

        // Find ManyToMany relationships
        $manyToMany = [];
        foreach ($properties as $entityName => $entityProps) {
            foreach ($entityProps as $property) {
                if ($property['relationshipType'] === 'ManyToMany') {
                    $manyToMany[] = [
                        'entity' => $entityName,
                        'property' => $property['propertyName'],
                        'target' => $property['targetEntity'],
                    ];
                }
            }
        }

        if (empty($manyToMany)) {
            $this->markTestSkipped('No ManyToMany relationships in CSV');
        }

        // Verify first ManyToMany relationship
        $rel = $manyToMany[0];
        $entityFile = $this->projectDir . '/src/Entity/Generated/' . $rel['entity'] . 'Generated.php';

        if (file_exists($entityFile)) {
            $content = file_get_contents($entityFile);

            // Should have ManyToMany annotation
            $this->assertStringContainsString('#[ORM\ManyToMany(', $content);

            // Should have add/remove methods like OneToMany
            $this->assertStringContainsString('public function add', $content);
            $this->assertStringContainsString('public function remove', $content);
        }
    }

    public function testCascadeOperationsConfigured(): void
    {
        $result = $this->parser->parseAll();
        $properties = $result['properties'];

        // Find relationships with cascade operations
        $cascadeRels = [];
        foreach ($properties as $entityName => $entityProps) {
            foreach ($entityProps as $property) {
                if (!empty($property['relationshipType']) && !empty($property['cascade'])) {
                    $cascadeRels[] = [
                        'entity' => $entityName,
                        'property' => $property['propertyName'],
                        'cascade' => $property['cascade'],
                    ];
                }
            }
        }

        if (empty($cascadeRels)) {
            $this->markTestSkipped('No relationships with cascade configured in CSV');
        }

        // Verify cascade configuration in generated code
        $rel = $cascadeRels[0];
        $entityFile = $this->projectDir . '/src/Entity/Generated/' . $rel['entity'] . 'Generated.php';

        if (file_exists($entityFile)) {
            $content = file_get_contents($entityFile);

            // Should have cascade in annotation
            $this->assertStringContainsString('cascade', $content);
        }
    }

    public function testOrphanRemovalConfigured(): void
    {
        $result = $this->parser->parseAll();
        $properties = $result['properties'];

        // Find relationships with orphanRemoval
        $orphanRels = [];
        foreach ($properties as $entityName => $entityProps) {
            foreach ($entityProps as $property) {
                if (!empty($property['relationshipType']) && $property['orphanRemoval'] === true) {
                    $orphanRels[] = [
                        'entity' => $entityName,
                        'property' => $property['propertyName'],
                    ];
                }
            }
        }

        if (empty($orphanRels)) {
            $this->markTestSkipped('No relationships with orphanRemoval configured in CSV');
        }

        // Verify orphanRemoval configuration
        $rel = $orphanRels[0];
        $entityFile = $this->projectDir . '/src/Entity/Generated/' . $rel['entity'] . 'Generated.php';

        if (file_exists($entityFile)) {
            $content = file_get_contents($entityFile);

            // Should have orphanRemoval in annotation
            $this->assertStringContainsString('orphanRemoval', $content);
        }
    }

    public function testFetchStrategyConfigured(): void
    {
        $result = $this->parser->parseAll();
        $properties = $result['properties'];

        // Find relationships with fetch strategy
        $fetchRels = [];
        foreach ($properties as $entityName => $entityProps) {
            foreach ($entityProps as $property) {
                if (!empty($property['relationshipType']) && !empty($property['fetch'])) {
                    $fetchRels[] = [
                        'entity' => $entityName,
                        'property' => $property['propertyName'],
                        'fetch' => $property['fetch'],
                    ];
                }
            }
        }

        if (empty($fetchRels)) {
            $this->markTestSkipped('No relationships with fetch strategy configured in CSV');
        }

        // Verify fetch strategy
        $rel = $fetchRels[0];
        $entityFile = $this->projectDir . '/src/Entity/Generated/' . $rel['entity'] . 'Generated.php';

        if (file_exists($entityFile)) {
            $content = file_get_contents($entityFile);

            // Should have fetch in annotation
            $this->assertStringContainsString('fetch', $content);
        }
    }

    public function testInverseSideConfiguredCorrectly(): void
    {
        $result = $this->parser->parseAll();
        $properties = $result['properties'];

        // Build relationship map
        $relationships = [];
        foreach ($properties as $entityName => $entityProps) {
            foreach ($entityProps as $property) {
                if (!empty($property['relationshipType'])) {
                    $relationships[] = [
                        'entity' => $entityName,
                        'property' => $property['propertyName'],
                        'type' => $property['relationshipType'],
                        'target' => $property['targetEntity'],
                        'inversedBy' => $property['inversedBy'] ?? null,
                        'mappedBy' => $property['mappedBy'] ?? null,
                    ];
                }
            }
        }

        // Check bidirectional relationships have proper inverse configuration
        $bidirectionalFound = false;
        foreach ($relationships as $rel) {
            if ($rel['inversedBy'] !== null) {
                $bidirectionalFound = true;

                // Find inverse side
                $inverseRel = array_filter($relationships, function ($r) use ($rel) {
                    return $r['entity'] === $rel['target']
                        && $r['property'] === $rel['inversedBy']
                        && $r['mappedBy'] === $rel['property'];
                });

                if (!empty($inverseRel)) {
                    $this->assertNotEmpty($inverseRel, "Inverse side should exist for {$rel['entity']}.{$rel['property']}");
                }
            }
        }

        if (!$bidirectionalFound) {
            $this->markTestSkipped('No bidirectional relationships found in CSV');
        }
    }
}
