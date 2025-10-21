<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ProfileGenerated;
use App\Repository\ProfileRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Profile Entity
 *
 * User profiles with permissions and preferences *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: ProfileRepository::class)]
#[ORM\Table(name: 'profile')]
class Profile extends ProfileGenerated
{
    // Add custom properties here

    // Add custom methods here
}
