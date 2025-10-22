<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\LostReasonGenerated;
use App\Repository\LostReasonRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * LostReason Entity
 *
 * Tracks and categorizes reasons for lost deals with advanced analytics capabilities. Supports win-loss analysis, competitor tracking, and actionable insights to improve win rates. Implements CRM best practices for structured data collection and longitudinal analysis. *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: LostReasonRepository::class)]
#[ORM\Table(name: 'lost_reason')]
class LostReason extends LostReasonGenerated
{
    // Add custom properties here

    // Add custom methods here
}
