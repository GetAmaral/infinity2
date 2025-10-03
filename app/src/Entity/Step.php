<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StepRepository;
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
 */
#[ORM\Entity(repositoryClass: StepRepository::class)]
class Step extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: TreeFlow::class, inversedBy: 'steps')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['step:read'])]
    protected TreeFlow $treeFlow;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['step:read', 'step:write'])]
    protected bool $first = false;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['step:read', 'step:write'])]
    protected string $name = '';

    #[ORM\Column(length: 255)]
    #[Groups(['step:read'])]
    protected string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?string $objective = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['step:read', 'step:write'])]
    protected ?string $prompt = null;

    #[ORM\OneToMany(mappedBy: 'step', targetEntity: Question::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
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

    /**
     * @return Collection<int, Question>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    public function addQuestion(Question $question): self
    {
        if (!$this->questions->contains($question)) {
            $this->questions->add($question);
            $question->setStep($this);
        }
        return $this;
    }

    public function removeQuestion(Question $question): self
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

    public function __toString(): string
    {
        return $this->name;
    }
}
