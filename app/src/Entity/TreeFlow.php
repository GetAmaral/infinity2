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
use Doctrine\ORM\Event\PreUpdateEventArgs;
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
#[ORM\HasLifecycleCallbacks]
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

    #[ORM\Column(length: 255)]
    #[Groups(['treeflow:read'])]
    protected string $slug = '';

    #[ORM\Column(type: 'integer')]
    #[Groups(['treeflow:read', 'treeflow:write'])]
    protected int $version = 1;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['treeflow:read', 'treeflow:write'])]
    protected bool $active = true;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['treeflow:read', 'treeflow:write'])]
    protected ?array $canvasViewState = null;

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
        $this->version = 1; // Start at version 1
    }

    #[ORM\PreUpdate]
    public function incrementVersion(\Doctrine\ORM\Event\PreUpdateEventArgs $event): void
    {
        // Get the changed fields
        $changeSet = $event->getEntityChangeSet();

        // Skip version increment if only canvasViewState changed
        // (AuditSubscriber already prevents updatedAt/updatedBy from being set for canvas-only changes)
        $nonVersionableFields = ['canvasViewState'];

        $meaningfulChanges = array_diff(array_keys($changeSet), $nonVersionableFields);

        // Only increment version if there are meaningful changes
        if (!empty($meaningfulChanges)) {
            $this->version++;
        }
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): self
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

    public function getCanvasViewState(): ?array
    {
        return $this->canvasViewState;
    }

    public function setCanvasViewState(?array $canvasViewState): self
    {
        $this->canvasViewState = $canvasViewState;
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
