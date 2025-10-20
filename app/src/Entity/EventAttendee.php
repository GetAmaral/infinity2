<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\EventAttendeeGenerated;
use App\Repository\EventAttendeeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * EventAttendee Entity
 *
 * Comprehensive event attendee tracking with RSVP status, check-in management, and attendance confirmation *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: EventAttendeeRepository::class)]
#[ORM\Table(name: 'event_attendee')]
class EventAttendee extends EventAttendeeGenerated
{
    // Add custom properties here

    // Add custom methods here
}
