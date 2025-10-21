<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Generated\RoleGenerated;
use App\Repository\RoleRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
class Role extends RoleGenerated
{
    // Custom permission helper methods
    public function addPermission(string $permission): self
    {
        if (!in_array($permission, $this->permissions, true)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    public function removePermission(string $permission): self
    {
        $this->permissions = array_values(array_filter(
            $this->permissions,
            fn($p) => $p !== $permission
        ));
        return $this;
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}