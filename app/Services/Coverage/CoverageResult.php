<?php

namespace App\Services\Coverage;

use App\Models\Certificate;

final readonly class CoverageResult
{
    public function __construct(
        public CoverageRequirement $requirement,
        public ?Certificate $certificate,
    ) {}

    public function isCovered(): bool
    {
        return $this->certificate !== null;
    }
}
