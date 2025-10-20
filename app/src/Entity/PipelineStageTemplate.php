<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\PipelineStageTemplateGenerated;
use App\Repository\PipelineStageTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PipelineStageTemplate Entity
 *
 * Templates for pipeline stages *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: PipelineStageTemplateRepository::class)]
#[ORM\Table(name: 'pipeline_stage_template')]
class PipelineStageTemplate extends PipelineStageTemplateGenerated
{
    // Add custom properties here

    // Add custom methods here
}
