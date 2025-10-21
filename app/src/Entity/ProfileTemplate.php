<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ProfileTemplateGenerated;
use App\Repository\ProfileTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProfileTemplate Entity
 *
 * Templates for user profiles with predefined permissions *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: ProfileTemplateRepository::class)]
#[ORM\Table(name: 'profile_template')]
class ProfileTemplate extends ProfileTemplateGenerated
{
    // Add custom properties here

    // Add custom methods here
}
