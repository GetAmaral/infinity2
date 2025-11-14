<?php

declare(strict_types=1);

namespace App\Message;

class ProcessTalkMessageCommand
{
    public function __construct(
        private readonly string $talkMessageId
    ) {
    }

    public function getTalkMessageId(): string
    {
        return $this->talkMessageId;
    }
}
