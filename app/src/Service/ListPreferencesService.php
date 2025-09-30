<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service to manage list view preferences (search, sort, filter, pagination)
 * Stores preferences in User->listPreferences JSON field and localStorage
 */
final class ListPreferencesService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Security $security
    ) {}

    /**
     * Get all list preferences for the current user
     */
    public function getPreferences(): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return $this->getDefaultPreferences();
        }

        $preferences = $user->getListPreferences();

        return $preferences ?? $this->getDefaultPreferences();
    }

    /**
     * Get a specific list preference
     */
    public function getPreference(string $key, mixed $default = null): mixed
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return $default;
        }

        return $user->getListPreference($key, $default);
    }

    /**
     * Save a specific list preference
     */
    public function savePreference(string $key, mixed $value): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->setListPreference($key, $value);
        $this->entityManager->flush();
    }

    /**
     * Save multiple list preferences at once
     */
    public function savePreferences(array $preferences): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $current = $user->getListPreferences() ?? [];
        $updated = array_merge($current, $preferences);

        $user->setListPreferences($updated);
        $this->entityManager->flush();
    }

    /**
     * Get list preferences for a specific entity (organizations, users, etc.)
     */
    public function getEntityPreferences(string $entityName): array
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return $this->getDefaultEntityPreferences();
        }

        $allPreferences = $user->getListPreferences() ?? [];

        return $allPreferences[$entityName] ?? $this->getDefaultEntityPreferences();
    }

    /**
     * Save list preferences for a specific entity
     */
    public function saveEntityPreferences(string $entityName, array $preferences): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $allPreferences = $user->getListPreferences() ?? [];
        $allPreferences[$entityName] = $preferences;

        $user->setListPreferences($allPreferences);
        $this->entityManager->flush();
    }

    /**
     * Clear all list preferences
     */
    public function clearPreferences(): void
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->setListPreferences(null);
        $this->entityManager->flush();
    }

    /**
     * Default preferences structure
     */
    private function getDefaultPreferences(): array
    {
        return [
            'organizations' => $this->getDefaultEntityPreferences(),
            'users' => $this->getDefaultEntityPreferences(),
        ];
    }

    /**
     * Default entity preferences
     */
    private function getDefaultEntityPreferences(): array
    {
        return [
            'view' => 'grid', // grid, list, or table
            'itemsPerPage' => 10,
            'sortColumn' => null,
            'sortDirection' => 'asc',
            'searchTerm' => '',
            'filters' => [],
            'columnFilters' => [], // For table view column-specific filters
            'currentPage' => 1,
        ];
    }
}