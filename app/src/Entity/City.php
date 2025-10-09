<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CityGenerated;
use App\Repository\CityRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * City Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: CityRepository::class)]
#[ORM\Table(name: 'city')]
class City extends CityGenerated
{
    // Add custom properties here

    // Add custom methods here
}
