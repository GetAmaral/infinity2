<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\WinReasonGenerated;
use App\Repository\WinReasonRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * WinReason Entity
 *
 * Reason for winning a deal
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: WinReasonRepository::class)]
#[ORM\Table(name: 'win_reason')]
class WinReason extends WinReasonGenerated
{
    // Add custom properties here

    // Add custom methods here
}
