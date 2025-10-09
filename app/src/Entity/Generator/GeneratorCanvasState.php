<?php

declare(strict_types=1);

namespace App\Entity\Generator;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class GeneratorCanvasState
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private int $id = 1; // Singleton - always ID 1

    #[ORM\Column(type: 'float', options: ['default' => 1.0])]
    private float $scale = 1.0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $offsetX = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $offsetY = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): int { return $this->id; }
    public function getScale(): float { return $this->scale; }
    public function setScale(float $scale): self { $this->scale = $scale; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getOffsetX(): int { return $this->offsetX; }
    public function setOffsetX(int $offsetX): self { $this->offsetX = $offsetX; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getOffsetY(): int { return $this->offsetY; }
    public function setOffsetY(int $offsetY): self { $this->offsetY = $offsetY; $this->updatedAt = new \DateTimeImmutable(); return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
}
