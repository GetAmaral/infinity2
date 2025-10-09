<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CompanyGenerated;
use App\Repository\CompanyRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Company Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'company')]
class Company extends CompanyGenerated
{
    // Add custom properties here

    // Add custom methods here
}
