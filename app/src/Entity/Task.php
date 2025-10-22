<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TaskGenerated;
use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Task Entity
 *
 * Tasks and to-dos for productivity management *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
class Task extends TaskGenerated
{
    // Add custom properties here

    // Add custom methods here
}
