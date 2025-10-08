<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Test fixtures for generator testing
 *
 * Creates organizations and sample data for testing generated entities
 */
class GeneratorTestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create test organizations
        $org1 = new Organization();
        $org1->setName('Test Organization 1');
        $org1->setSlug('test-org-1');
        $org1->setDescription('Test organization for generator testing');
        $manager->persist($org1);

        $org2 = new Organization();
        $org2->setName('Test Organization 2');
        $org2->setSlug('test-org-2');
        $org2->setDescription('Second test organization');
        $manager->persist($org2);

        $manager->flush();

        // Store references for other fixtures
        $this->addReference('org-1', $org1);
        $this->addReference('org-2', $org2);
    }
}
