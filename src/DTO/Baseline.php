<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

final readonly class Baseline
{
    public function __construct(
        public CrapCheckResult $crapCheckResult
    )
    {
    }
}