<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TaxCategoryGenerated;
use App\Repository\TaxCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * TaxCategory Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: TaxCategoryRepository::class)]
#[ORM\Table(name: 'tax_category')]
class TaxCategory extends TaxCategoryGenerated
{
    // Add custom properties here

    // Add custom methods here
}
