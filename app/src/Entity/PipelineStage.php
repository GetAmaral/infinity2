<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\PipelineStageGenerated;
use App\Repository\PipelineStageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PipelineStage Entity
 *
 * Pipeline stage configurations *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: PipelineStageRepository::class)]
#[ORM\Table(name: 'pipeline_stage')]
class PipelineStage extends PipelineStageGenerated
{
    // Add custom properties here

    // Add custom methods here
}
