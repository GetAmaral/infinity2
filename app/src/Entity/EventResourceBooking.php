<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\EventResourceBookingGenerated;
use App\Repository\EventResourceBookingRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * EventResourceBooking Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: EventResourceBookingRepository::class)]
#[ORM\Table(name: 'event_resource_booking')]
class EventResourceBooking extends EventResourceBookingGenerated
{
    // Add custom properties here

    // Add custom methods here
}
