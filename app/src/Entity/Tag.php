<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TagGenerated;
use App\Repository\TagRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tag Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tag')]
class Tag extends TagGenerated
{
    // Add custom properties here

    // Add custom methods here
}
