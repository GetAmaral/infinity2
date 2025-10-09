<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ProductCategoryGenerated;
use App\Repository\ProductCategoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * ProductCategory Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: ProductCategoryRepository::class)]
#[ORM\Table(name: 'product_category')]
class ProductCategory extends ProductCategoryGenerated
{
    // Add custom properties here

    // Add custom methods here
}
