<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TaskTypeGenerated;
use App\Repository\TaskTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TaskType Entity
 *
 * Task types for categorization (Call, Email, Meeting, etc.) *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: TaskTypeRepository::class)]
#[ORM\Table(name: 'task_type')]
class TaskType extends TaskTypeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
