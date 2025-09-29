<?php

namespace App\DataFixtures;

use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Acme Corporation Users
        $acmeUsers = [
            ['Wile E. Coyote', 'wile.coyote@acme.corp'],
            ['Marvin the Martian', 'marvin.martian@acme.corp'],
            ['Porky Pig', 'porky.pig@acme.corp'],
        ];

        foreach ($acmeUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setOrganization($this->getReference(OrganizationFixtures::ORG_ACME_REFERENCE, Organization::class));
            $manager->persist($user);
        }

        // Globex Corporation Users
        $globexUsers = [
            ['Hank Scorpio', 'hank.scorpio@globex.com'],
            ['Homer Simpson', 'homer.simpson@globex.com'],
            ['Mindy Simmons', 'mindy.simmons@globex.com'],
        ];

        foreach ($globexUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setOrganization($this->getReference(OrganizationFixtures::ORG_GLOBEX_REFERENCE, Organization::class));
            $manager->persist($user);
        }

        // Wayne Enterprises Users
        $wayneUsers = [
            ['Bruce Wayne', 'bruce.wayne@wayneenterprises.com'],
            ['Lucius Fox', 'lucius.fox@wayneenterprises.com'],
            ['Alfred Pennyworth', 'alfred.pennyworth@wayneenterprises.com'],
            ['Tim Drake', 'tim.drake@wayneenterprises.com'],
        ];

        foreach ($wayneUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setOrganization($this->getReference(OrganizationFixtures::ORG_WAYNETECH_REFERENCE, Organization::class));
            $manager->persist($user);
        }

        // Stark Industries Users
        $starkUsers = [
            ['Tony Stark', 'tony.stark@starkindustries.com'],
            ['Pepper Potts', 'pepper.potts@starkindustries.com'],
            ['Happy Hogan', 'happy.hogan@starkindustries.com'],
            ['James Rhodes', 'james.rhodes@starkindustries.com'],
        ];

        foreach ($starkUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setOrganization($this->getReference(OrganizationFixtures::ORG_STARK_REFERENCE, Organization::class));
            $manager->persist($user);
        }

        // Umbrella Corporation Users
        $umbrellaUsers = [
            ['Albert Wesker', 'albert.wesker@umbrella.corp'],
            ['William Birkin', 'william.birkin@umbrella.corp'],
            ['Annette Birkin', 'annette.birkin@umbrella.corp'],
        ];

        foreach ($umbrellaUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setOrganization($this->getReference(OrganizationFixtures::ORG_UMBRELLA_REFERENCE, Organization::class));
            $manager->persist($user);
        }

        // Users without organizations
        $independentUsers = [
            ['John Doe', 'john.doe@example.com'],
            ['Jane Smith', 'jane.smith@freelance.net'],
            ['Bob Johnson', 'bob.johnson@consultant.org'],
        ];

        foreach ($independentUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            // No organization assigned
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixtures::class,
        ];
    }
}