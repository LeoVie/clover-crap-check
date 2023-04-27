<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

class NonEmptyCrapCheckResult implements CrapCheckResult
{
    /** @param array<Method> $tooCrappyMethods */
    public function __construct(
        public readonly array $tooCrappyMethods
    )
    {
    }
}