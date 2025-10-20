<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\CalendarExternalLinkGenerated;
use App\Repository\CalendarExternalLinkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Calendar External Link Entity
 *
 * Manages OAuth-based external calendar integrations (Google Calendar, Microsoft Outlook, Apple Calendar) with bi-directional sync, webhook support, and token refresh management *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom business logic, methods, and overrides here.
 *
 * @generated once by Genmax Code Generator
 */
#[ORM\Entity(repositoryClass: CalendarExternalLinkRepository::class)]
#[ORM\Table(name: 'calendar_external_link')]
class CalendarExternalLink extends CalendarExternalLinkGenerated
{
    // Add custom properties here

    // Add custom methods here
}
