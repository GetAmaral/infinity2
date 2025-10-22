<?php

declare(strict_types=1);

namespace App\MultiTenant;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Unified processor for automatic tenant/organization assignment
 *
 * Handles BOTH:
 * 1. API Platform requests (via ProcessorInterface)
 * 2. Doctrine entity persists (via Doctrine event listener)
 *
 * Logic:
 * - Get organization from TenantContext (single source of truth!)
 * - Auto-assign to entities with organization field
 * - Allow explicit override if org already set
 *
 * Works for:
 * - API Platform POST/PUT operations
 * - Form-based entity creation
 * - CLI commands and fixtures
 * - Any Doctrine persist operation
 */
#[AsDoctrineListener(event: Events::prePersist)]
final class TenantEntityProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor, // Decorated processor for API Platform
        private readonly TenantContext $tenantContext,
        private readonly Security $security,
        private readonly LoggerInterface $logger
    ) {
    }

    // ========================================
    // API PLATFORM PROCESSING
    // ========================================

    /**
     * Process API Platform operations (POST, PUT)
     *
     * @param mixed $data
     * @param Operation $operation
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     * @return mixed
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): mixed {
        // Only process POST (create) and PUT (replace)
        $method = $operation->getMethod();
        if ($method === 'POST' || $method === 'PUT') {
            $this->assignTenantToEntity($data, 'api_platform');

            // Also set owner from authenticated user (API-specific)
            $this->assignOwnerToEntity($data);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    // ========================================
    // DOCTRINE LIFECYCLE PROCESSING
    // ========================================

    /**
     * Handle Doctrine prePersist event
     * Works for ALL entity creations (forms, CLI, fixtures, etc.)
     */
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $this->assignTenantToEntity($entity, 'doctrine');
    }

    // ========================================
    // SHARED LOGIC
    // ========================================

    /**
     * Auto-assign tenant/organization to entity
     *
     * 🔒 SECURITY: Non-admin users CANNOT override organization - it's always set from tenant context
     * Only ADMIN/SUPER_ADMIN can explicitly set a different organization
     */
    private function assignTenantToEntity(object $entity, string $source): void
    {
        // Check if entity has organization field
        if (!method_exists($entity, 'setOrganization') || !method_exists($entity, 'getOrganization')) {
            return;
        }

        // Get tenant from TenantContext (single source of truth!)
        $tenant = $this->tenantContext->getTenant();

        if (!$tenant) {
            // No tenant in context - this might be:
            // - Root domain admin access
            // - Public page access
            // - CLI command without tenant context
            $this->logger->warning('Entity created without tenant context', [
                'entity_class' => get_class($entity),
                'source' => $source,
            ]);
            return;
        }

        // Check if user is admin (only admins can override organization)
        $user = $this->security->getUser();
        $isAdmin = $user instanceof \App\Entity\User &&
                   ($this->security->isGranted('ROLE_ADMIN') ||
                    $this->security->isGranted('ROLE_SUPER_ADMIN'));

        // Check if organization already set
        $existingOrg = null;
        try {
            $existingOrg = $entity->getOrganization();
        } catch (\Throwable $e) {
            // getOrganization() might throw if uninitialized - continue
        }

        if ($existingOrg instanceof Organization && $existingOrg->getId()) {
            if ($isAdmin) {
                // ✅ Admin override allowed - validate organization exists and log
                $this->logger->warning('Admin explicitly set organization on entity', [
                    'entity_class' => get_class($entity),
                    'admin_user_id' => $user->getId()->toRfc4122(),
                    'explicit_org_id' => $existingOrg->getId()->toRfc4122(),
                    'tenant_context_id' => $tenant->getId()->toRfc4122(),
                    'source' => $source,
                ]);
                return; // Allow admin override
            } else {
                // 🔒 Non-admin tried to set organization - ALWAYS override with tenant context
                $this->logger->warning('Non-admin attempted to set organization - overriding with tenant context', [
                    'entity_class' => get_class($entity),
                    'user_id' => $user?->getId()?->toRfc4122(),
                    'attempted_org_id' => $existingOrg->getId()->toRfc4122(),
                    'enforced_tenant_id' => $tenant->getId()->toRfc4122(),
                    'source' => $source,
                ]);
                // Fall through to force assignment
            }
        }

        // Auto-assign tenant (overwrites non-admin attempts)
        $entity->setOrganization($tenant);

        $this->logger->info('Tenant auto-assigned to entity', [
            'entity_class' => get_class($entity),
            'tenant_id' => $tenant->getId()->toRfc4122(),
            'tenant_slug' => $tenant->getSlug(),
            'source' => $source,
            'was_override' => isset($existingOrg),
        ]);
    }

    /**
     * Auto-assign owner to entity (for entities with owner field)
     */
    private function assignOwnerToEntity(object $entity): void
    {
        if (!method_exists($entity, 'setOwner') || !method_exists($entity, 'getOwner')) {
            return;
        }

        // Check if owner already set
        try {
            $existingOwner = $entity->getOwner();
            if ($existingOwner instanceof User && $existingOwner->getId()) {
                return; // Already set, don't override
            }
        } catch (\Throwable $e) {
            // getOwner() might throw if uninitialized - continue
        }

        // Get authenticated user
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            return; // No user or not a User instance
        }

        // Auto-assign owner
        $entity->setOwner($user);

        $this->logger->debug('Owner auto-assigned to entity', [
            'entity_class' => get_class($entity),
            'owner_id' => $user->getId()->toRfc4122(),
        ]);
    }
}
