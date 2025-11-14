<?php

declare(strict_types=1);

namespace App\Message;

class EvaluateStepCompletionCommand
{
    public function __construct(
        private readonly string $talkId
    ) {
    }

    public function getTalkId(): string
    {
        return $this->talkId;
    }
}
