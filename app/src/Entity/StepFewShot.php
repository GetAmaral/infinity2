<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\FewShotType;
use App\Repository\StepFewShotRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * StepFewShot - Example for AI guidance (positive or negative)
 *
 * Provides concrete examples to guide AI behavior:
 * - POSITIVE: Shows correct/desired patterns
 * - NEGATIVE: Shows incorrect/undesired patterns (anti-patterns)
 */
#[ORM\Entity(repositoryClass: StepFewShotRepository::class)]
class StepFewShot extends EntityBase
{
    #[ORM\ManyToOne(targetEntity: StepQuestion::class, inversedBy: 'examples')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['fewshot:read'])]
    protected StepQuestion $question;

    #[ORM\Column(type: 'string', enumType: FewShotType::class)]
    #[Groups(['fewshot:read', 'fewshot:write'])]
    protected FewShotType $type = FewShotType::POSITIVE;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['fewshot:read', 'fewshot:write'])]
    protected string $name = '';

    #[ORM\Column(length: 255)]
    #[Groups(['fewshot:read'])]
    protected string $slug = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['fewshot:read', 'fewshot:write'])]
    protected ?string $prompt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['fewshot:read', 'fewshot:write'])]
    protected ?string $description = null;

    public function getQuestion(): StepQuestion
    {
        return $this->question;
    }

    public function setQuestion(?StepQuestion $question): self
    {
        $this->question = $question;
        return $this;
    }

    public function getType(): FewShotType
    {
        return $this->type;
    }

    public function setType(FewShotType $type): self
    {
        $this->type = $type;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function isPositive(): bool
    {
        return $this->type === FewShotType::POSITIVE;
    }

    public function isNegative(): bool
    {
        return $this->type === FewShotType::NEGATIVE;
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s', $this->type->value, $this->name);
    }
}
