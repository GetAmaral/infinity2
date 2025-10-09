<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ProductGenerated;
use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Product Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'product')]
class Product extends ProductGenerated
{
    // Add custom properties here

    // Add custom methods here
}
