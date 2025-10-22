<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ContactGenerated;
use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contact Entity
 *
 * Customer contacts with full profile and interaction history *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\Table(name: 'contact')]
class Contact extends ContactGenerated
{
    // Add custom properties here

    // Add custom methods here
}
