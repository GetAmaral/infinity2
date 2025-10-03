<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * FewShotType enum for AI guidance examples
 *
 * POSITIVE: Examples of correct/desired behavior
 * NEGATIVE: Examples of incorrect/undesired behavior (anti-patterns)
 */
enum FewShotType: string
{
    case POSITIVE = 'positive';
    case NEGATIVE = 'negative';

    /**
     * Get a human-readable label for the type
     */
    public function getLabel(): string
    {
        return match($this) {
            self::POSITIVE => 'Positive Example',
            self::NEGATIVE => 'Negative Example',
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
