<?php

declare(strict_types=1);

namespace App\MultiTenant;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * Doctrine filter that automatically adds tenant filtering to queries
 * for entities that have an organization relationship
 *
 * This filter is enabled/disabled by TenantGuard based on current tenant context
 */
final class TenantDataFilter extends SQLFilter
{
    /**
     * Add WHERE clause to filter by tenant/organization
     */
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Check if entity has 'organization' field
        if (!$targetEntity->hasAssociation('organization')) {
            return '';
        }

        // Try to get the tenant ID from filter parameter
        try {
            $tenantId = $this->getParameter('tenant_id');
        } catch (\InvalidArgumentException $e) {
            // Parameter not set - filter was enabled but parameter wasn't provided
            // This shouldn't happen, but if it does, don't filter
            return '';
        }

        // If no tenant ID is set, don't filter (admins at root domain)
        if ($tenantId === null || $tenantId === 'null' || $tenantId === '') {
            return '';
        }

        // Get the column name for organization_id
        $associationMapping = $targetEntity->getAssociationMapping('organization');
        $organizationColumn = $associationMapping->joinColumns[0]->name ?? 'organization_id';

        // Return the WHERE clause
        return sprintf('%s.%s = %s', $targetTableAlias, $organizationColumn, $tenantId);
    }
}
