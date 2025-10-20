<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StepRepository;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Step - A single step in the TreeFlow workflow
 *
 * Each step contains:
 * - Questions for the AI to answer
 * - Outputs defining possible next steps based on conditions
 * - Inputs defining how this step can be entered
 *
 * Performance optimizations:
 * - Indexed by tree_flow_id for fast lookup
 * - Indexed by (tree_flow_id, first) for first step queries
 * - Indexed by slug for quick slug-based lookups
 * - Composite index on (tree_flow_id, view_order) for sorted queries
 */
#[ORM\Entity(repositoryClass: StepRepository::class)]
#[ORM\Table(name: 'step')]
#[ORM\Index(name: 'idx_step_treeflow_first', columns: ['tree_flow_id', 'first'])]
#[ORM\Index(name: 'idx_step_slug', columns: ['slug'])]
#[ORM\Index(name: 'idx_step_treeflow_order', columns: ['tree_flow_id', 'view_order'])]
#[ORM\Index(name: 'idx_step_active', columns: ['active'])]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    routePrefix: '/steps',
    normalizationContext: ['groups' => ['step:read']],
    denormalizationContext: ['groups' => ['step:write']],
    operations: [
        new Get(
            uriTemplate: '/{id}',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['step:read', 'question:read', 'output:read', 'input:read']]
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
            normalizationContext: ['groups' => ['step:read', 'question:read', 'output:read', 'input:read']]
        ),
        // Get first step of TreeFlow
        new Get(
            uriTemplate: '/treeflow/{treeflowId}/first',
            security: "is_granted('ROLE_USER')",
            normalizationContext: ['groups' => ['step:read', 'question:read', 'output:read', 'input:read']]
        ),
        // Admin endpoint with audit information
        new GetCollection(
            uriTemplate: '/admin/steps',
            security: "is_granted('ROLE_ADMIN')",
            normalizationContext: ['groups' => ['step:read', 'audit:read']]
        )
    ]
)]
class Step extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: TreeFlow::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['step:read'])]
    protected TreeFlow $treeFlow;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['step:read', 'step:write'])]
    protected bool $first = false;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['step:read', 'step:write'])]
    protected bool $active = true;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['step:read', 'step:write'])]
    protected bool $required = false;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['step:read', 'step:write'])]
    protected string $name = '';

    #[ORM\Column(length: 255)]
    #[Groups(['step:read'])]
    protected string $slug = '';

    #[ORM\Column(length: 50, nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?string $stepType = 'standard';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?string $objective = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?string $prompt = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['step:read', 'step:write'])]
    protected int $viewOrder = 1;

    #[ORM\Column(type: 'integer')]
    #[Groups(['step:read', 'step:write'])]
    protected int $displayOrder = 1;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?int $estimatedDuration = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\Range(min: 1, max: 10)]
    #[Groups(['step:read', 'step:write'])]
    protected ?int $priority = 5;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?int $positionX = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?int $positionY = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?array $metadata = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?array $tags = null;

    #[ORM\OneToMany(mappedBy: 'step', targetEntity: StepQuestion::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['step:read'])]
    protected Collection $questions;

    #[ORM\OneToMany(mappedBy: 'step', targetEntity: StepOutput::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['step:read'])]
    protected Collection $outputs;

    #[ORM\OneToMany(mappedBy: 'step', targetEntity: StepInput::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['step:read'])]
    protected Collection $inputs;

    public function __construct()
    {
        parent::__construct();
        $this->questions = new ArrayCollection();
        $this->outputs = new ArrayCollection();
        $this->inputs = new ArrayCollection();
    }

    public function getTreeFlow(): TreeFlow
    {
        return $this->treeFlow;
    }

    public function setTreeFlow(?TreeFlow $treeFlow): self
    {
        $this->treeFlow = $treeFlow;
        return $this;
    }

    public function isFirst(): bool
    {
        return $this->first;
    }

    public function setFirst(bool $first): self
    {
        $this->first = $first;
        return $this;
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

    public function getObjective(): ?string
    {
        return $this->objective;
    }

    public function setObjective(?string $objective): self
    {
        $this->objective = $objective;
        return $this;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt;
        return $this;
    }

    public function getViewOrder(): int
    {
        return $this->viewOrder;
    }

    public function setViewOrder(int $viewOrder): self
    {
        $this->viewOrder = $viewOrder;
        return $this;
    }

    /**
     * @return Collection<int, StepQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(StepQuestion $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setStep($this);
        }
        return $this;
    }

    public function removeQuestion(StepQuestion $question): self
    {
        if ($this->questions->removeElement($question)) {
            if ($question->getStep() === $this) {
                $question->setStep(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, StepOutput>
     */
    public function getOutputs(): Collection
    {
        return $this->outputs;
    }

    public function addOutput(StepOutput $output): self
    {
        if (!$this->outputs->contains($output)) {
            $this->outputs->add($output);
            $output->setStep($this);
        }
        return $this;
    }

    public function removeOutput(StepOutput $output): self
    {
        if ($this->outputs->removeElement($output)) {
            if ($output->getStep() === $this) {
                $output->setStep(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, StepInput>
     */
    public function getInputs(): Collection
    {
        return $this->inputs;
    }

    public function addInput(StepInput $input): self
    {
        if (!$this->inputs->contains($input)) {
            $this->inputs->add($input);
            $input->setStep($this);
        }
        return $this;
    }

    public function removeInput(StepInput $input): self
    {
        if ($this->inputs->removeElement($input)) {
            if ($input->getStep() === $this) {
                $input->setStep(null);
            }
        }
        return $this;
    }

    public function getPositionX(): ?int
    {
        return $this->positionX;
    }

    public function setPositionX(?int $positionX): self
    {
        $this->positionX = $positionX;
        return $this;
    }

    public function getPositionY(): ?int
    {
        return $this->positionY;
    }

    public function setPositionY(?int $positionY): self
    {
        $this->positionY = $positionY;
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

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    public function getStepType(): ?string
    {
        return $this->stepType;
    }

    public function setStepType(?string $stepType): self
    {
        $this->stepType = $stepType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): self
    {
        $this->displayOrder = $displayOrder;
        return $this;
    }

    public function getEstimatedDuration(): ?int
    {
        return $this->estimatedDuration;
    }

    public function setEstimatedDuration(?int $estimatedDuration): self
    {
        $this->estimatedDuration = $estimatedDuration;
        return $this;
    }

    public function getPriority(): ?int
    {
        return $this->priority ?? 5;
    }

    public function setPriority(?int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags ?? [];
    }

    public function setTags(?array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Add a tag to the step
     */
    public function addTag(string $tag): self
    {
        $tags = $this->getTags();
        if (!in_array($tag, $tags, true)) {
            $tags[] = $tag;
            $this->tags = $tags;
        }
        return $this;
    }

    /**
     * Remove a tag from the step
     */
    public function removeTag(string $tag): self
    {
        $tags = $this->getTags();
        $this->tags = array_values(array_filter($tags, fn($t) => $t !== $tag));
        return $this;
    }

    /**
     * Check if step has a specific tag
     */
    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->getTags(), true);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
