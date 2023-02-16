<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

readonly class Baseline
{
    public function __construct(
        public CrapCheckResult $crapCheckResult
    )
    {
    }
}