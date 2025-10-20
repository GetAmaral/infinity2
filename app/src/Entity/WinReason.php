<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\WinReasonGenerated;
use App\Repository\WinReasonRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Win Reason Entity
 *
 * Tracks reasons for won deals to analyze success patterns and competitive positioning *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: WinReasonRepository::class)]
#[ORM\Table(name: 'win_reason')]
class WinReason extends WinReasonGenerated
{
    // Add custom properties here

    // Add custom methods here
}
