<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\StepGenerated;
use App\Repository\StepRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Step - A single step in the TreeFlow workflow
 *
 * Each step contains:
 * - Actions for the AI to perform or questions to answer
 * - Outputs defining possible next steps based on conditions
 * - Inputs defining how this step can be entered
 *
 * Performance optimizations:
 * - Indexed by tree_flow_id for fast lookup
 * - Indexed by (tree_flow_id, first) for first step queries
 * - Indexed by slug for quick slug-based lookups
 * - Composite index on (tree_flow_id, view_order) for sorted queries
 *
 * Extends generated base class - only add custom business logic here
 */
#[ORM\Entity(repositoryClass: StepRepository::class)]
#[ORM\Table(name: 'step')]
#[ORM\Index(name: 'idx_step_treeflow_first', columns: ['tree_flow_id', 'first_prop'])]
#[ORM\Index(name: 'idx_step_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_step_treeflow_order', columns: ['tree_flow_id', 'view_order'])]
#[ApiResource(
    routePrefix: '/steps',
    normalizationContext: ['groups' => ['step:read']],
    denormalizationContext: ['groups' => ['step:write']],
    operations: [
        new Get(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['step:read', 'action:read', 'output:read', 'input:read']]
        ),
        new GetCollection(
            uriTemplate: '',
            security: "is_granted('ROLE_USER')"
        ),
        new Post(
            uriTemplate: '',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Put(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Patch(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        new Delete(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_ADMIN')"
        ),
        // Get steps by TreeFlow
        new GetCollection(
            uriTemplate: '/treeflow/{treeflowId}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['step:read', 'action:read', 'output:read', 'input:read']]
        ),
        // Get first step of TreeFlow
        new Get(
            uriTemplate: '/treeflow/{treeflowId}/first',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['step:read', 'action:read', 'output:read', 'input:read']]
        ),
        // Admin endpoint with audit information
        new GetCollection(
            uriTemplate: '/admin/steps',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['step:read', 'audit:read']]
        )
    ]
)]
class Step extends StepGenerated
{
    // === OVERRIDE __toString ===

    public function __toString(): string
    {
        return $this->name;
    }
}
