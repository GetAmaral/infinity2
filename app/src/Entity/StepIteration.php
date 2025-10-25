<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\StepIterationGenerated;
use App\Repository\StepIterationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepIteration Entity
 *
 * Decision questions in workflow logic *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: StepIterationRepository::class)]
#[ORM\Table(name: 'step_iteration')]
class StepIteration extends StepIterationGenerated
{
    // Add custom properties here

    // Add custom methods here
}
