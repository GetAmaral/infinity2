<?php

namespace App\Tests\Doctrine;

use App\Doctrine\UuidV7Generator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

class UuidV7GeneratorTest extends TestCase
{
    /**
     */
    public function testGenerateId(): void
    {
        $generator = new UuidV7Generator();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();

        $uuid = $generator->generateId($entityManager, $entity);

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertTrue(Uuid::isValid($uuid->toRfc4122()));
    }

    /**
     */
    public function testIsPostInsertGenerator(): void
    {
        $generator = new UuidV7Generator();

        $this->assertFalse($generator->isPostInsertGenerator());
    }

    /**
     */
    public function testGenerateUniqueIds(): void
    {
        $generator = new UuidV7Generator();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();

        $uuid1 = $generator->generateId($entityManager, $entity);
        $uuid2 = $generator->generateId($entityManager, $entity);

        $this->assertNotEquals($uuid1->toString(), $uuid2->toString());
    }

    /**
     */
    public function testGenerateChronologicalIds(): void
    {
        $generator = new UuidV7Generator();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entity = new \stdClass();

        $uuid1 = $generator->generateId($entityManager, $entity);

        // Small delay to ensure different timestamp
        usleep(1000);

        $uuid2 = $generator->generateId($entityManager, $entity);

        // UUIDv7 should be chronologically ordered
        $this->assertLessThan($uuid2->toString(), $uuid1->toString());
    }
}