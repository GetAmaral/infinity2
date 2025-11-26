<?php

declare(strict_types=1);

namespace App\Message;

class AnalyzeDealCommand
{
    public function __construct(
        private readonly string $dealId
    ) {}

    public function getDealId(): string
    {
        return $this->dealId;
    }
}
