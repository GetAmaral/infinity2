<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ModuleGenerated;
use App\Repository\ModuleRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Module Entity
 *
 * System modules for role-based access control *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: ModuleRepository::class)]
#[ORM\Table(name: 'module_table')]
class Module extends ModuleGenerated
{
    // Add custom properties here

    // Add custom methods here
}
