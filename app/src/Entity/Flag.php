<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\FlagGenerated;
use App\Repository\FlagRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Flag Entity
 *
 * Categorizable flags and labels for follow-ups, reminders, and entity tagging with polymorphic relationships *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: FlagRepository::class)]
#[ORM\Table(name: 'flag')]
class Flag extends FlagGenerated
{
    // Add custom properties here

    // Add custom methods here
}
