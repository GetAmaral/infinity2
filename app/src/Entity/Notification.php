<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\NotificationGenerated;
use App\Repository\NotificationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Notification Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notification')]
class Notification extends NotificationGenerated
{
    // Add custom properties here

    // Add custom methods here
}
