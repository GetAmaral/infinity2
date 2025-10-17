<?php

declare(strict_types=1);

namespace App\Message;

class ProcessVideoMessage
{
    public function __construct(
        private readonly string $lectureId,
        private readonly string $originalFilePath
    ) {}

    public function getLectureId(): string
    {
        return $this->lectureId;
    }

    public function getOriginalFilePath(): string
    {
        return $this->originalFilePath;
    }
}
