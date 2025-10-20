<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\PipelineTemplateGenerated;
use App\Repository\PipelineTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * PipelineTemplate Entity
 *
 * Pipeline templates for quick setup *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: PipelineTemplateRepository::class)]
#[ORM\Table(name: 'pipeline_template')]
class PipelineTemplate extends PipelineTemplateGenerated
{
    // Add custom properties here

    // Add custom methods here
}
