<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\MeetingDataGenerated;
use App\Repository\MeetingDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Meeting Data Entity
 *
 * Meeting data including links, notes, recordings, agenda, minutes, and attendee tracking for comprehensive meeting management *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: MeetingDataRepository::class)]
#[ORM\Table(name: 'meeting_data')]
class MeetingData extends MeetingDataGenerated
{
    // Add custom properties here

    // Add custom methods here
}
