<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\StepInput;
use App\Entity\StepOutput;
use App\Repository\StepConnectionRepository;

/**
 * StepConnectionValidator - Validates connection creation rules
 *
 * Validation Rules:
 * 1. No self-loops (Step A → Step A)
 * 2. One StepOutput can only have one connection
 * 3. No duplicate connections (same output → same input)
 */
class StepConnectionValidator
{
    public function __construct(
        private readonly StepConnectionRepository $connectionRepository
    ) {
    }

    /**
     * Validate if a connection can be created
     *
     * @return array{valid: bool, error?: string}
     */
    public function validate(StepOutput $output, StepInput $input): array
    {
        // Rule 1: No self-loops
        if ($output->getStep()->getId() === $input->getStep()->getId()) {
            return [
                'valid' => false,
                'error' => 'Cannot connect step to itself',
            ];
        }

        // Rule 2: Output can only have one connection
        if ($output->hasConnection()) {
            return [
                'valid' => false,
                'error' => 'Output already has a connection. Delete existing connection first.',
            ];
        }

        // Rule 3: No duplicate output→input pairs
        if ($this->connectionRepository->connectionExists($output, $input)) {
            return [
                'valid' => false,
                'error' => 'Connection already exists between this output and input',
            ];
        }

        return ['valid' => true];
    }
}
