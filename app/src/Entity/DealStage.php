<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\DealStageGenerated;
use App\Repository\DealStageRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * DealStage Entity
 *
 * Stages within sales pipelines *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: DealStageRepository::class)]
#[ORM\Table(name: 'deal_stage')]
class DealStage extends DealStageGenerated
{
    // Add custom properties here

    // Add custom methods here
}
