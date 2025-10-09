<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\NotificationTypeTemplateGenerated;
use App\Repository\NotificationTypeTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * NotificationTypeTemplate Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: NotificationTypeTemplateRepository::class)]
#[ORM\Table(name: 'notification_type_template')]
class NotificationTypeTemplate extends NotificationTypeTemplateGenerated
{
    // Add custom properties here

    // Add custom methods here
}
