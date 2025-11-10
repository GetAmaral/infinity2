<?php

declare(strict_types=1);

namespace App\Dto;

use App\Dto\Generated\CalendarExternalLinkInputDtoGenerated;

/**
 * Calendar External Link Input DTO
 *
 * Manages OAuth-based external calendar integrations (Google Calendar, Microsoft Outlook, Apple Calendar) with bi-directional sync, webhook support, and token refresh management *
 * This class extends the generated base and is SAFE TO EDIT.
 * Add custom validation rules, transformations, and business logic here.
 *
 * Examples:
 * - Custom validation constraints
 * - Custom getters/setters with business logic
 * - Transformation methods for complex data
 *
 * @generated once by Genmax Code Generator
 */
class CalendarExternalLinkInputDto extends CalendarExternalLinkInputDtoGenerated
{
    // Add custom properties here

    // Add custom validation constraints here
    // Example:
    // #[Assert\Callback]
    // public function validateCustomRule(ExecutionContextInterface $context): void
    // {
    //     if ($this->startDate > $this->endDate) {
    //         $context->buildViolation('Start date must be before end date')
    //             ->atPath('startDate')
    //             ->addViolation();
    //     }
    // }

    // Add custom methods here
}
