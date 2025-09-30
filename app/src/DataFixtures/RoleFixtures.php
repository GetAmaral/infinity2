<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public const USER_ROLE_REFERENCE = 'role-user';
    public const ADMIN_ROLE_REFERENCE = 'role-admin';
    public const MANAGER_ROLE_REFERENCE = 'role-manager';

    public function load(ObjectManager $manager): void
    {
        // Create Admin Role
        $adminRole = new Role();
        $adminRole->setName('admin');
        $adminRole->setDescription('System Administrator with full access');
        $adminRole->setPermissions([
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'organization.view',
            'organization.create',
            'organization.edit',
            'organization.delete',
            'role.view',
            'role.create',
            'role.edit',
            'role.delete',
            'settings.view',
            'settings.edit',
            'api.full_access',
        ]);
        $adminRole->setIsSystem(true);
        $manager->persist($adminRole);
        $this->addReference(self::ADMIN_ROLE_REFERENCE, $adminRole);

        // Create Manager Role
        $managerRole = new Role();
        $managerRole->setName('manager');
        $managerRole->setDescription('Manager with organization management access');
        $managerRole->setPermissions([
            'user.view',
            'user.create',
            'user.edit',
            'organization.view',
            'organization.create',
            'organization.edit',
            'api.read',
            'api.write',
        ]);
        $managerRole->setIsSystem(false);
        $manager->persist($managerRole);
        $this->addReference(self::MANAGER_ROLE_REFERENCE, $managerRole);

        // Create User Role
        $userRole = new Role();
        $userRole->setName('user');
        $userRole->setDescription('Standard user with basic access');
        $userRole->setPermissions([
            'user.view',
            'organization.view',
            'api.read',
        ]);
        $userRole->setIsSystem(false);
        $manager->persist($userRole);
        $this->addReference(self::USER_ROLE_REFERENCE, $userRole);

        $manager->flush();
    }
}