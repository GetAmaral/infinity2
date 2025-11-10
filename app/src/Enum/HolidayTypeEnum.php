<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * HolidayTypeEnum
 *
 * Enum for different types of holidays
 */
enum HolidayTypeEnum: string
{
    case NATIONAL = 'national';
    case REGIONAL = 'regional';
    case RELIGIOUS = 'religious';
    case CUSTOM = 'custom';

    /**
     * Get a human-readable label for the holiday type
     */
    public function getLabel(): string
    {
        return match($this) {
            self::NATIONAL => 'National Holiday',
            self::REGIONAL => 'Regional Holiday',
            self::RELIGIOUS => 'Religious Holiday',
            self::CUSTOM => 'Custom Holiday',
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
