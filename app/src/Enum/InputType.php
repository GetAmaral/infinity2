<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * InputType enum for Step entry conditions
 *
 * FULLY_COMPLETED: Entry triggered when previous step is fully completed
 * NOT_COMPLETED_AFTER_ATTEMPTS: Entry triggered when previous step failed after X attempts
 * ANY: Entry can be triggered regardless of previous step status
 */
enum InputType: string
{
    case FULLY_COMPLETED = 'fully_completed';
    case NOT_COMPLETED_AFTER_ATTEMPTS = 'not_completed_after_attempts';
    case ANY = 'any';

    /**
     * Get a human-readable label for the type
     */
    public function getLabel(): string
    {
        return match($this) {
            self::FULLY_COMPLETED => 'Fully Completed',
            self::NOT_COMPLETED_AFTER_ATTEMPTS => 'Not Completed After Attempts',
            self::ANY => 'Any Status',
        };
    }

    /**
     * Get all available types as array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
