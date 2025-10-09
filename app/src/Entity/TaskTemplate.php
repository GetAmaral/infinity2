<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TaskTemplateGenerated;
use App\Repository\TaskTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TaskTemplate Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: TaskTemplateRepository::class)]
#[ORM\Table(name: 'task_template')]
class TaskTemplate extends TaskTemplateGenerated
{
    // Add custom properties here

    // Add custom methods here
}
