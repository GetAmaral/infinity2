<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service for managing user UI preferences and settings
 *
 * This service provides a centralized way to:
 * - Save user preferences to database
 * - Load user preferences on login
 * - Apply default settings for new users
 * - Validate and sanitize preference values
 */
class UserPreferencesService
{
    private const VALID_THEMES = ['dark', 'light', 'auto'];
    private const VALID_LOCALES = ['en', 'pt_BR'];
    private const VALID_LAYOUTS = ['grid', 'list', 'card'];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security,
        #[Autowire(service: 'monolog.logger.business')]
        private readonly LoggerInterface $businessLogger
    ) {}

    /**
     * Get current user's preferences with fallback to defaults
     */
    public function getUserPreferences(?User $user = null): array
    {
        $user = $user ?? $this->getCurrentUser();

        if (!$user) {
            return $this->getDefaultPreferences();
        }

        // Initialize preferences if not set
        if ($user->getUiSettings() === null) {
            $user->initializeUiSettings();
            $this->saveUserPreferences($user, $user->getUiSettings());
        }

        return array_merge($this->getDefaultPreferences(), $user->getUiSettings() ?? []);
    }

    /**
     * Save user preferences to database
     */
    public function saveUserPreferences(?User $user, array $preferences): bool
    {
        $user = $user ?? $this->getCurrentUser();

        if (!$user) {
            $this->businessLogger->warning('Attempted to save preferences without authenticated user');
            return false;
        }

        try {
            // Validate and sanitize preferences
            $sanitizedPreferences = $this->sanitizePreferences($preferences);

            // Merge with existing preferences
            $user->mergeUiSettings($sanitizedPreferences);

            $this->entityManager->flush();

            $this->businessLogger->info('User preferences saved successfully', [
                'user_id' => $this->getUserIdSafely($user),
                'preferences' => $sanitizedPreferences
            ]);

            return true;

        } catch (\Exception $e) {
            $this->businessLogger->error('Failed to save user preferences', [
                'user_id' => $this->getUserIdSafely($user),
                'error' => $e->getMessage(),
                'preferences' => $preferences
            ]);

            return false;
        }
    }

    /**
     * Save a specific preference value
     */
    public function savePreference(string $key, mixed $value, ?User $user = null): bool
    {
        return $this->saveUserPreferences($user, [$key => $value]);
    }

    /**
     * Get a specific preference value
     */
    public function getPreference(string $key, mixed $default = null, ?User $user = null): mixed
    {
        $preferences = $this->getUserPreferences($user);
        return $preferences[$key] ?? $default;
    }

    /**
     * Reset user preferences to defaults
     */
    public function resetUserPreferences(?User $user = null): bool
    {
        $user = $user ?? $this->getCurrentUser();

        if (!$user) {
            return false;
        }

        try {
            $user->setUiSettings($this->getDefaultPreferences());
            $this->entityManager->flush();

            $this->businessLogger->info('User preferences reset to defaults', [
                'user_id' => $this->getUserIdSafely($user)
            ]);

            return true;

        } catch (\Exception $e) {
            $this->businessLogger->error('Failed to reset user preferences', [
                'user_id' => $this->getUserIdSafely($user),
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Export user preferences as JSON
     */
    public function exportUserPreferences(?User $user = null): string
    {
        $preferences = $this->getUserPreferences($user);
        return json_encode($preferences, JSON_PRETTY_PRINT);
    }

    /**
     * Import user preferences from JSON
     */
    public function importUserPreferences(string $json, ?User $user = null): bool
    {
        try {
            $preferences = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            if (!is_array($preferences)) {
                throw new \InvalidArgumentException('Invalid preferences format');
            }

            return $this->saveUserPreferences($user, $preferences);

        } catch (\Exception $e) {
            $this->businessLogger->error('Failed to import user preferences', [
                'error' => $e->getMessage(),
                'json' => $json
            ]);

            return false;
        }
    }

    /**
     * Get default preferences structure
     */
    private function getDefaultPreferences(): array
    {
        return [
            'theme' => 'dark',
            'locale' => 'en',
            'sidebar_collapsed' => false,
            'notifications_enabled' => true,
            'auto_save' => true,
            'animations_enabled' => true,
            'dashboard_layout' => 'grid',
            'items_per_page' => 25,
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'currency' => 'USD',
            'sound_enabled' => true,
            'compact_mode' => false
        ];
    }

    /**
     * Validate and sanitize preference values
     */
    private function sanitizePreferences(array $preferences): array
    {
        $sanitized = [];

        foreach ($preferences as $key => $value) {
            $sanitized[$key] = match($key) {
                'theme' => in_array($value, self::VALID_THEMES) ? $value : 'dark',
                'locale' => in_array($value, self::VALID_LOCALES) ? $value : 'en',
                'dashboard_layout' => in_array($value, self::VALID_LAYOUTS) ? $value : 'grid',
                'items_per_page' => max(10, min(100, (int) $value)),
                'sidebar_collapsed', 'notifications_enabled', 'auto_save',
                'animations_enabled', 'sound_enabled', 'compact_mode' => (bool) $value,
                'timezone' => $this->isValidTimezone((string) $value) ? $value : 'UTC',
                'date_format', 'time_format', 'currency' => $this->sanitizeString((string) $value),
                default => $value // Allow other preferences but log them
            };

            // Log unknown preferences for monitoring
            if (!array_key_exists($key, $this->getDefaultPreferences())) {
                $this->businessLogger->info('Unknown preference key saved', [
                    'key' => $key,
                    'value' => $value
                ]);
            }
        }

        return $sanitized;
    }

    /**
     * Get currently authenticated user
     */
    private function getCurrentUser(): ?User
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user : null;
    }

    /**
     * Check if timezone is valid
     */
    private function isValidTimezone(string $timezone): bool
    {
        return in_array($timezone, timezone_identifiers_list());
    }

    /**
     * Sanitize string values
     */
    private function sanitizeString(string $value): string
    {
        return trim(strip_tags($value));
    }

    /**
     * Safely get user ID for logging, handling uninitialized cases
     */
    private function getUserIdSafely(User $user): string
    {
        try {
            return $user->getId()->toString();
        } catch (\Error $e) {
            return 'uninitialized-user';
        }
    }
}