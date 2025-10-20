<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\DealCategoryGenerated;
use App\Repository\DealCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * DealCategory Entity
 *
 * Deal categories for reporting and analysis *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: DealCategoryRepository::class)]
#[ORM\Table(name: 'deal_category')]
class DealCategory extends DealCategoryGenerated
{
    // Add custom properties here

    // Add custom methods here
}
