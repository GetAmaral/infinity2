<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\AttachmentGenerated;
use App\Repository\AttachmentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Attachment Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: AttachmentRepository::class)]
#[ORM\Table(name: 'attachment')]
class Attachment extends AttachmentGenerated
{
    // Add custom properties here

    // Add custom methods here
}
