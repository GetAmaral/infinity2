<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\TalkGenerated;
use App\Repository\TalkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Talk Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: TalkRepository::class)]
#[ORM\Table(name: 'talk')]
class Talk extends TalkGenerated
{
    // Add custom properties here

    // Add custom methods here
}
