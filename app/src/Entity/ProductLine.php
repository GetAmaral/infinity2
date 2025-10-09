<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ProductLineGenerated;
use App\Repository\ProductLineRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProductLine Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: ProductLineRepository::class)]
#[ORM\Table(name: 'product_line')]
class ProductLine extends ProductLineGenerated
{
    // Add custom properties here

    // Add custom methods here
}
