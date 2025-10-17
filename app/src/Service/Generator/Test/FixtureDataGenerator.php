<?php

declare(strict_types=1);

namespace App\Service\Generator\Test;

use App\Service\Generator\Csv\PropertyDefinitionDto;

/**
 * Fixture Data Generator
 *
 * Generates realistic test data based on property types and names
 * Uses Faker library for data generation
 */
class FixtureDataGenerator
{
    /**
     * Get Faker method for property
     */
    public function getFakerMethod(PropertyDefinitionDto $property): string
    {
        // Use explicit fixtureType if specified
        if ($property->fixtureType) {
            return $this->mapFixtureType($property->fixtureType);
        }

        // Infer from property name
        $propertyName = strtolower($property->propertyName);

        // Name fields
        if (str_contains($propertyName, 'firstname')) {
            return 'firstName()';
        }
        if (str_contains($propertyName, 'lastname')) {
            return 'lastName()';
        }
        if (str_contains($propertyName, 'name') || str_contains($propertyName, 'fullname')) {
            return 'name()';
        }

        // Contact fields
        if (str_contains($propertyName, 'email')) {
            return 'email()';
        }
        if (str_contains($propertyName, 'phone') || str_contains($propertyName, 'telephone')) {
            return 'phoneNumber()';
        }

        // Address fields
        if (str_contains($propertyName, 'address')) {
            return 'address()';
        }
        if (str_contains($propertyName, 'city')) {
            return 'city()';
        }
        if (str_contains($propertyName, 'country')) {
            return 'country()';
        }
        if (str_contains($propertyName, 'zipcode') || str_contains($propertyName, 'postalcode')) {
            return 'postcode()';
        }
        if (str_contains($propertyName, 'street')) {
            return 'streetAddress()';
        }

        // Company fields
        if (str_contains($propertyName, 'company')) {
            return 'company()';
        }

        // Web fields
        if (str_contains($propertyName, 'url') || str_contains($propertyName, 'website')) {
            return 'url()';
        }

        // Description/Content fields
        if (str_contains($propertyName, 'description') || str_contains($propertyName, 'content') || str_contains($propertyName, 'notes')) {
            return 'paragraph()';
        }
        if (str_contains($propertyName, 'title')) {
            return 'sentence(3)';
        }

        // Infer from property type
        return match ($property->propertyType) {
            'boolean' => 'boolean()',
            'integer', 'smallint', 'bigint' => 'numberBetween(1, 100)',
            'decimal', 'float' => 'randomFloat(2, 0, 1000)',
            'date' => 'date()',
            'datetime', 'datetime_immutable' => 'dateTime()',
            'text' => 'paragraph()',
            default => 'word()',
        };
    }

    /**
     * Map fixtureType from CSV to Faker method
     */
    private function mapFixtureType(string $fixtureType): string
    {
        return match ($fixtureType) {
            'name' => 'name()',
            'firstName' => 'firstName()',
            'lastName' => 'lastName()',
            'email' => 'email()',
            'phoneNumber' => 'phoneNumber()',
            'address' => 'address()',
            'city' => 'city()',
            'country' => 'country()',
            'company' => 'company()',
            'url' => 'url()',
            'word' => 'word()',
            'sentence' => 'sentence()',
            'paragraph' => 'paragraph()',
            'text' => 'text()',
            'boolean' => 'boolean()',
            'number' => 'numberBetween(1, 100)',
            'float' => 'randomFloat(2, 0, 1000)',
            'date' => 'date()',
            'datetime' => 'dateTime()',
            default => 'word()',
        };
    }

    /**
     * Generate PHP value for property (for tests that don't use Faker)
     */
    public function getPhpValue(PropertyDefinitionDto $property): string
    {
        return match ($property->propertyType) {
            'boolean' => 'true',
            'integer', 'smallint', 'bigint' => '123',
            'decimal', 'float' => '99.99',
            'date' => 'new \DateTime()',
            'datetime', 'datetime_immutable' => 'new \DateTimeImmutable()',
            'text' => "'Test description'",
            default => "'Test " . ucfirst($property->propertyName) . "'",
        };
    }
}
