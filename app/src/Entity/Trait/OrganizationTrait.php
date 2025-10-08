<?php

declare(strict_types=1);

namespace App\Entity\Trait;

use App\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * OrganizationTrait provides multi-tenant organization support for entities
 *
 * This trait links entities to their parent organization for tenant isolation.
 * The OrganizationFilter automatically filters queries based on the active organization.
 *
 * Usage:
 * 1. Add this trait to entities that need organization-level isolation
 * 2. The OrganizationFilter will automatically apply WHERE conditions
 * 3. Use serialization groups to control API visibility
 */
trait OrganizationTrait
{
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['read', 'organization:read'])]
    protected Organization $organization;

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }
}
