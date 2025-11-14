<?php

declare(strict_types=1);

namespace App\Message;

class GenerateAgentResponseCommand
{
    public function __construct(
        private readonly string $talkId,
        private readonly ?string $contextMessage = null
    ) {
    }

    public function getTalkId(): string
    {
        return $this->talkId;
    }

    public function getContextMessage(): ?string
    {
        return $this->contextMessage;
    }
}
