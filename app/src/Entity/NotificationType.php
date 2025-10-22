<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\NotificationTypeGenerated;
use App\Repository\NotificationTypeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * NotificationType Entity
 *
 * Notification types for the organization *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: NotificationTypeRepository::class)]
#[ORM\Table(name: 'notification_type')]
class NotificationType extends NotificationTypeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
