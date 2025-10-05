<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StepQuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * StepQuestion - A question for the AI to answer within a Step
 *
 * Questions guide the AI's decision-making process with:
 * - A prompt defining what to ask
 * - An objective explaining the purpose
 * - Importance weighting (1-10)
 * - Few-shot examples (positive and negative)
 */
#[ORM\Entity(repositoryClass: StepQuestionRepository::class)]
class StepQuestion extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: Step::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['question:read'])]
    protected Step $step;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['question:read', 'question:write'])]
    protected string $name = '';

    #[ORM\Column(length: 255)]
    #[Groups(['question:read'])]
    protected string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['question:read', 'question:write'])]
    protected ?string $prompt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['question:read', 'question:write'])]
    protected ?string $objective = null;

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 10)]
    #[Groups(['question:read', 'question:write'])]
    protected int $importance = 5;

    #[ORM\Column(type: 'integer')]
    #[Groups(['question:read', 'question:write'])]
    protected int $viewOrder = 1;

    #[ORM\OneToMany(mappedBy: 'question', targetEntity: StepFewShot::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['question:read'])]
    protected Collection $examples;

    public function __construct()
    {
        parent::__construct();
        $this->examples = new ArrayCollection();
    }

    public function getStep(): Step
    {
        return $this->step;
    }

    public function setStep(?Step $step): self
    {
        $this->step = $step;
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

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(?string $prompt): self
    {
        $this->prompt = $prompt;
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

    public function getImportance(): int
    {
        return $this->importance;
    }

    public function setImportance(int $importance): self
    {
        $this->importance = $importance;
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
     * @return Collection<int, StepFewShot>
     */
    public function getFewShotExamples(): Collection
    {
        return $this->examples;
    }

    public function addFewShotExample(StepFewShot $example): self
    {
        if (!$this->examples->contains($example)) {
            $this->examples->add($example);
            $example->setQuestion($this);
        }
        return $this;
    }

    public function removeFewShotExample(StepFewShot $example): self
    {
        if ($this->examples->removeElement($example)) {
            if ($example->getQuestion() === $this) {
                $example->setQuestion(null);
            }
        }
        return $this;
    }

    /**
     * Get only positive few-shot examples
     */
    public function getPositiveFewShotExamples(): Collection
    {
        return $this->examples->filter(
            fn(StepFewShot $example) => $example->getType() === \App\Enum\FewShotType::POSITIVE
        );
    }

    /**
     * Get only negative few-shot examples
     */
    public function getNegativeFewShotExamples(): Collection
    {
        return $this->examples->filter(
            fn(StepFewShot $example) => $example->getType() === \App\Enum\FewShotType::NEGATIVE
        );
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
