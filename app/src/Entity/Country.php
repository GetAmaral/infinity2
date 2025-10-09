<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CountryGenerated;
use App\Repository\CountryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Country Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: CountryRepository::class)]
#[ORM\Table(name: 'country')]
class Country extends CountryGenerated
{
    // Add custom properties here

    // Add custom methods here
}
