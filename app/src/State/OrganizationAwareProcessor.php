<?php

declare(strict_types=1);

namespace App\State;

use App\Entity\User;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * API Platform State Processor that automatically sets organization and owner
 * for entities created via the API.
 *
 * This processor ensures that when entities are created via API requests:
 * 1. The organization is set from the authenticated user's organization
 * 2. The owner is set from the authenticated user
 *
 * This implements the OrganizationAware pattern for API Platform operations.
 */
final class OrganizationAwareProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
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
        // Only process on POST (create) and PUT (replace) operations
        $method = $operation->getMethod();
        if ($method !== 'POST' && $method !== 'PUT') {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();

        if (!$user instanceof User) {
            $this->logger->warning('OrganizationAwareProcessor: No authenticated user found for API request');
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $organization = $user->getOrganization();

        if (!$organization) {
            $this->logger->warning('OrganizationAwareProcessor: User has no organization', [
                'user_id' => $user->getId()->toRfc4122(),
                'user_email' => $user->getEmail(),
            ]);
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Set organization if the entity has a setOrganization method
        if (method_exists($data, 'setOrganization') && method_exists($data, 'getOrganization')) {
            try {
                // Only set if not already set (allow explicit override in request)
                $existingOrg = $data->getOrganization();
                if ($existingOrg === null || !$existingOrg->getId()) {
                    $data->setOrganization($organization);
                    $this->logger->debug('OrganizationAwareProcessor: Set organization', [
                        'entity' => get_class($data),
                        'organization_id' => $organization->getId()->toRfc4122(),
                    ]);
                }
            } catch (\Throwable $e) {
                // getOrganization() might throw error if not initialized
                $data->setOrganization($organization);
                $this->logger->debug('OrganizationAwareProcessor: Set organization (uninitialized)', [
                    'entity' => get_class($data),
                    'organization_id' => $organization->getId()->toRfc4122(),
                ]);
            }
        }

        // Set owner if the entity has a setOwner method
        if (method_exists($data, 'setOwner') && method_exists($data, 'getOwner')) {
            try {
                // Only set if not already set (allow explicit override in request)
                $existingOwner = $data->getOwner();
                if ($existingOwner === null || !$existingOwner->getId()) {
                    $data->setOwner($user);
                    $this->logger->debug('OrganizationAwareProcessor: Set owner', [
                        'entity' => get_class($data),
                        'owner_id' => $user->getId()->toRfc4122(),
                    ]);
                }
            } catch (\Throwable $e) {
                // getOwner() might throw error if not initialized
                $data->setOwner($user);
                $this->logger->debug('OrganizationAwareProcessor: Set owner (uninitialized)', [
                    'entity' => get_class($data),
                    'owner_id' => $user->getId()->toRfc4122(),
                ]);
            }
        }

        // Set createdBy if the entity has a setCreatedBy method (audit trail)
        if (method_exists($data, 'setCreatedBy')) {
            try {
                if (method_exists($data, 'getCreatedBy')) {
                    $existingCreator = $data->getCreatedBy();
                    if ($existingCreator === null || !$existingCreator->getId()) {
                        $data->setCreatedBy($user);
                    }
                }
            } catch (\Throwable $e) {
                $data->setCreatedBy($user);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
