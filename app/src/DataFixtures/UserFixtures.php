<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Organization;
use App\Entity\Role;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $adminRole = $this->getReference(RoleFixtures::ADMIN_ROLE_REFERENCE, Role::class);
        $managerRole = $this->getReference(RoleFixtures::MANAGER_ROLE_REFERENCE, Role::class);
        $userRole = $this->getReference(RoleFixtures::USER_ROLE_REFERENCE, Role::class);

        // Create Admin User
        $admin = new User();
        $admin->setName('Admin User');
        $admin->setEmail('admin@luminai.ai');
        $admin->setPassword($this->passwordHasher->hashPassword($admin, '1'));
        $admin->setIsVerified(true);
        $admin->addRole($adminRole);
        $admin->generateApiToken(90); // 90 days for admin
        $manager->persist($admin);

        // Avelum Organization Users
        $acmeOrg = $this->getReference(OrganizationFixtures::ORG_AVELUM_REFERENCE, Organization::class);

        $acmeManager = new User();
        $acmeManager->setName('Nicolas Lucano S Amaral');
        $acmeManager->setEmail('nicolas@avelum.ia.br');
        $acmeManager->setPassword($this->passwordHasher->hashPassword($acmeManager, '123'));
        $acmeManager->setIsVerified(true);
        $acmeManager->setOrganization($acmeOrg);
        $acmeManager->addRole($managerRole);
        $acmeManager->generateApiToken(30);
        $manager->persist($acmeManager);

        $acmeUsers = [
            ['Luan Vieira Mendes', 'luan@avelum.ia.br'],
        ];

        foreach ($acmeUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, '123'));
            $user->setIsVerified(true);
            $user->setOrganization($acmeOrg);
            $user->addRole($managerRole);
            $manager->persist($user);
        }

        /*
        // Globex Corporation Users
        $globexOrg = $this->getReference(OrganizationFixtures::ORG_GLOBEX_REFERENCE, Organization::class);

        $globexManager = new User();
        $globexManager->setName('Hank Scorpio');
        $globexManager->setEmail('hank.scorpio@globex.com');
        $globexManager->setPassword($this->passwordHasher->hashPassword($globexManager, 'password123'));
        $globexManager->setIsVerified(true);
        $globexManager->setOrganization($globexOrg);
        $globexManager->addRole($managerRole);
        $manager->persist($globexManager);


        $globexUsers = [
            ['Homer Simpson', 'homer.simpson@globex.com'],
            ['Mindy Simmons', 'mindy.simmons@globex.com'],
        ];

        foreach ($globexUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setIsVerified(true);
            $user->setOrganization($globexOrg);
            $user->addRole($userRole);
            $manager->persist($user);
        }

        // Wayne Enterprises Users
        $wayneOrg = $this->getReference(OrganizationFixtures::ORG_WAYNETECH_REFERENCE, Organization::class);

        $wayneManager = new User();
        $wayneManager->setName('Bruce Wayne');
        $wayneManager->setEmail('bruce.wayne@wayneenterprises.com');
        $wayneManager->setPassword($this->passwordHasher->hashPassword($wayneManager, 'password123'));
        $wayneManager->setIsVerified(true);
        $wayneManager->setOrganization($wayneOrg);
        $wayneManager->addRole($managerRole);
        $manager->persist($wayneManager);

        $wayneUsers = [
            ['Lucius Fox', 'lucius.fox@wayneenterprises.com'],
            ['Alfred Pennyworth', 'alfred.pennyworth@wayneenterprises.com'],
            ['Tim Drake', 'tim.drake@wayneenterprises.com'],
        ];

        foreach ($wayneUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setIsVerified(true);
            $user->setOrganization($wayneOrg);
            $user->addRole($userRole);
            $manager->persist($user);
        }

        // Stark Industries Users
        $starkOrg = $this->getReference(OrganizationFixtures::ORG_STARK_REFERENCE, Organization::class);

        $starkManager = new User();
        $starkManager->setName('Tony Stark');
        $starkManager->setEmail('tony.stark@starkindustries.com');
        $starkManager->setPassword($this->passwordHasher->hashPassword($starkManager, 'password123'));
        $starkManager->setIsVerified(true);
        $starkManager->setOrganization($starkOrg);
        $starkManager->addRole($managerRole);
        $manager->persist($starkManager);

        $starkUsers = [
            ['Pepper Potts', 'pepper.potts@starkindustries.com'],
            ['Happy Hogan', 'happy.hogan@starkindustries.com'],
            ['James Rhodes', 'james.rhodes@starkindustries.com'],
        ];

        foreach ($starkUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setIsVerified(true);
            $user->setOrganization($starkOrg);
            $user->addRole($userRole);
            $manager->persist($user);
        }

        // Umbrella Corporation Users
        $umbrellaOrg = $this->getReference(OrganizationFixtures::ORG_UMBRELLA_REFERENCE, Organization::class);

        $umbrellaManager = new User();
        $umbrellaManager->setName('Albert Wesker');
        $umbrellaManager->setEmail('albert.wesker@umbrella.corp');
        $umbrellaManager->setPassword($this->passwordHasher->hashPassword($umbrellaManager, 'password123'));
        $umbrellaManager->setIsVerified(true);
        $umbrellaManager->setOrganization($umbrellaOrg);
        $umbrellaManager->addRole($managerRole);
        $manager->persist($umbrellaManager);

        $umbrellaUsers = [
            ['William Birkin', 'william.birkin@umbrella.corp'],
            ['Annette Birkin', 'annette.birkin@umbrella.corp'],
        ];

        foreach ($umbrellaUsers as [$name, $email]) {
            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setIsVerified(true);
            $user->setOrganization($umbrellaOrg);
            $user->addRole($userRole);
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
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));
            $user->setIsVerified(true);
            $user->addRole($userRole);
            $manager->persist($user);
        }
        */

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            OrganizationFixtures::class,
            RoleFixtures::class,
        ];
    }
}
