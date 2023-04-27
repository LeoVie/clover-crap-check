<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

class Baseline
{
    public function __construct(
        public readonly CrapCheckResult $crapCheckResult
    )
    {
    }
}