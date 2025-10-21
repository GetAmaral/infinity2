<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\OrganizationGenerated;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;

/**
 * Organization Entity
 *
 * Multi-tenant organization entity that isolates data by subdomain.
 * This class extends the generated base and contains custom business logic.
 */
#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['organization:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['organization:read']]
        ),
        new Post(
            denormalizationContext: ['groups' => ['organization:write']],
            normalizationContext: ['groups' => ['organization:read']]
        ),
        new Put(
            denormalizationContext: ['groups' => ['organization:write']],
            normalizationContext: ['groups' => ['organization:read']]
        ),
        new Patch(
            denormalizationContext: ['groups' => ['organization:write']],
            normalizationContext: ['groups' => ['organization:read']]
        ),
        new Delete()
    ],
    routePrefix: '/api'
)]
class Organization extends OrganizationGenerated
{
}
