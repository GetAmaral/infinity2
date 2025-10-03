<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TreeFlowRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * TreeFlow - AI Agent Guidance System
 *
 * A TreeFlow represents a complete workflow for AI agent guidance,
 * containing steps with questions, few-shot examples, and conditional routing.
 */
#[ORM\Entity(repositoryClass: TreeFlowRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['treeflow:read']],
    denormalizationContext: ['groups' => ['treeflow:write']],
    operations: [
        new Get(
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['treeflow:read', 'step:read', 'question:read', 'fewshot:read']]
        ),
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Put(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')"),
        // Admin endpoint with audit information
        new GetCollection(
            uriTemplate: '/admin/treeflows',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['treeflow:read', 'audit:read']]
        )
    ]
)]
class TreeFlow extends EntityBase
{
    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['treeflow:read', 'treeflow:write'])]
    protected string $name = '';

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['treeflow:read', 'treeflow:write'])]
    protected string $version = '1.0.0';

    #[ORM\Column(type: 'boolean')]
    #[Groups(['treeflow:read', 'treeflow:write'])]
    protected bool $active = true;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['treeflow:read'])]
    protected Organization $organization;

    #[ORM\OneToMany(mappedBy: 'treeFlow', targetEntity: Step::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['treeflow:read'])]
    protected Collection $steps;

    public function __construct()
    {
        parent::__construct();
        $this->steps = new ArrayCollection();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return Collection<int, Step>
     */
    public function getSteps(): Collection
    {
        return $this->steps;
    }

    public function addStep(Step $step): self
    {
        if (!$this->steps->contains($step)) {
            $this->steps->add($step);
            $step->setTreeFlow($this);
        }
        return $this;
    }

    public function removeStep(Step $step): self
    {
        if ($this->steps->removeElement($step)) {
            if ($step->getTreeFlow() === $this) {
                $step->setTreeFlow(null);
            }
        }
        return $this;
    }

    /**
     * Get the first step in this TreeFlow
     */
    public function getFirstStep(): ?Step
    {
        foreach ($this->steps as $step) {
            if ($step->isFirst()) {
                return $step;
            }
        }
        return null;
    }

    public function __toString(): string
    {
        return $this->name . ' v' . $this->version;
    }
}
