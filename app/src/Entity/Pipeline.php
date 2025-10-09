<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\PipelineGenerated;
use App\Repository\PipelineRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Pipeline Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: PipelineRepository::class)]
#[ORM\Table(name: 'pipeline')]
class Pipeline extends PipelineGenerated
{
    // Add custom properties here

    // Add custom methods here
}
