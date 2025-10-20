<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\ReminderGenerated;
use App\Repository\ReminderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Reminder Entity
 *
 * Automated reminders and notifications for events, tasks, meetings, and follow-ups with multi-channel delivery support *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: ReminderRepository::class)]
#[ORM\Table(name: 'reminder')]
class Reminder extends ReminderGenerated
{
    // Add custom properties here

    // Add custom methods here
}
