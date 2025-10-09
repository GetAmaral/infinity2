<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\SocialMediaGenerated;
use App\Repository\SocialMediaRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * SocialMedia Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: SocialMediaRepository::class)]
#[ORM\Table(name: 'social_media')]
class SocialMedia extends SocialMediaGenerated
{
    // Add custom properties here

    // Add custom methods here
}
