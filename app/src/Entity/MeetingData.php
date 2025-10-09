<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\MeetingDataGenerated;
use App\Repository\MeetingDataRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * MeetingData Entity
 *
 * 
 *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Luminai Code Generator
 */
#[ORM\Entity(repositoryClass: MeetingDataRepository::class)]
#[ORM\Table(name: 'meeting_data')]
class MeetingData extends MeetingDataGenerated
{
    // Add custom properties here

    // Add custom methods here
}
