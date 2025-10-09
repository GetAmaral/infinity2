<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\LostReasonGenerated;
use App\Repository\LostReasonRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * LostReason Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: LostReasonRepository::class)]
#[ORM\Table(name: 'lost_reason')]
class LostReason extends LostReasonGenerated
{
    // Add custom properties here

    // Add custom methods here
}
