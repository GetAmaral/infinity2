<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\BrandGenerated;
use App\Repository\BrandRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Brand Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: BrandRepository::class)]
#[ORM\Table(name: 'brand')]
class Brand extends BrandGenerated
{
    // Add custom properties here

    // Add custom methods here
}
