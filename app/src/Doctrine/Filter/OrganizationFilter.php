<?php

declare(strict_types=1);

namespace App\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Doctrine filter that automatically adds organization filtering to queries
 * for entities that have an organization relationship
 */
final class OrganizationFilter extends SQLFilter
{
    /**
     * Add WHERE clause to filter by organization
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Check if entity has 'organization' field
        if (!$targetEntity->hasAssociation('organization')) {
            return '';
        }

        // Try to get the organization ID from filter parameter
        try {
            $organizationId = $this->getParameter('organization_id');
        } catch (\InvalidArgumentException $e) {
            // Parameter not set - filter was enabled but parameter wasn't provided
            // This shouldn't happen, but if it does, don't filter
            return '';
        }

        // If no organization ID is set, don't filter (admins at root domain)
        if ($organizationId === null || $organizationId === 'null' || $organizationId === '') {
            return '';
        }

        // Get the column name for organization_id
        $organizationColumn = $targetEntity->getAssociationMapping('organization')['joinColumns'][0]['name'] ?? 'organization_id';

        // Return the WHERE clause
        return sprintf('%s.%s = %s', $targetTableAlias, $organizationColumn, $organizationId);
    }
}