<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\DealTypeGenerated;
use App\Repository\DealTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * DealType Entity
 *
 * Deal types for categorizing opportunities *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: DealTypeRepository::class)]
#[ORM\Table(name: 'deal_type')]
class DealType extends DealTypeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
