<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\UserGenerated;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * User Entity
 *
 * System users and authentication *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user_table')]
class User extends UserGenerated
{
    // Add custom properties here

    // Add custom methods here
}
