<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\StepActionGenerated;
use App\Repository\StepActionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * StepAction Entity
 *
 * Decision questions in workflow logic *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: StepActionRepository::class)]
#[ORM\Table(name: 'step_action')]
class StepAction extends StepActionGenerated
{
    // Add custom properties here

    // Add custom methods here
}
