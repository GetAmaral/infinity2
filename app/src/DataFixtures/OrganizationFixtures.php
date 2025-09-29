<?php

namespace App\DataFixtures;

use App\Entity\Organization;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OrganizationFixtures extends Fixture
{
    public const ORG_ACME_REFERENCE = 'org-acme';
    public const ORG_GLOBEX_REFERENCE = 'org-globex';
    public const ORG_WAYNETECH_REFERENCE = 'org-waynetech';
    public const ORG_STARK_REFERENCE = 'org-stark';
    public const ORG_UMBRELLA_REFERENCE = 'org-umbrella';

    public function load(ObjectManager $manager): void
    {
        // Create Acme Corporation
        $acme = new Organization();
        $acme->setName('Acme Corporation');
        $acme->setDescription('A fictional corporation that exists in the Warner Bros. universe, most prominently featured in the animated series featuring Wile E. Coyote and the Road Runner.');
        $manager->persist($acme);
        $this->addReference(self::ORG_ACME_REFERENCE, $acme);

        // Create Globex Corporation
        $globex = new Organization();
        $globex->setName('Globex Corporation');
        $globex->setDescription('A company from The Simpsons, owned by Hank Scorpio. Known for its friendly work environment and world domination plans.');
        $manager->persist($globex);
        $this->addReference(self::ORG_GLOBEX_REFERENCE, $globex);

        // Create Wayne Enterprises
        $waynetech = new Organization();
        $waynetech->setName('Wayne Enterprises');
        $waynetech->setDescription('A multinational conglomerate owned by Bruce Wayne, primarily known for its cutting-edge technology and philanthropic efforts in Gotham City.');
        $manager->persist($waynetech);
        $this->addReference(self::ORG_WAYNETECH_REFERENCE, $waynetech);

        // Create Stark Industries
        $stark = new Organization();
        $stark->setName('Stark Industries');
        $stark->setDescription('A multinational corporation led by Tony Stark, specializing in advanced technology, defense contracts, and clean energy solutions.');
        $manager->persist($stark);
        $this->addReference(self::ORG_STARK_REFERENCE, $stark);

        // Create Umbrella Corporation
        $umbrella = new Organization();
        $umbrella->setName('Umbrella Corporation');
        $umbrella->setDescription('A multinational pharmaceutical company from the Resident Evil universe, known for its biotechnology research and development.');
        $manager->persist($umbrella);
        $this->addReference(self::ORG_UMBRELLA_REFERENCE, $umbrella);

        $manager->flush();
    }
}