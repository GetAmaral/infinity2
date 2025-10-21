<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\SocialMediaTypeGenerated;
use App\Repository\SocialMediaTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * SocialMediaType Entity
 *
 * Social media platform types (Facebook, LinkedIn, Twitter, etc.) *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: SocialMediaTypeRepository::class)]
#[ORM\Table(name: 'social_media_type')]
class SocialMediaType extends SocialMediaTypeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
