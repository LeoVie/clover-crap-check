<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

final readonly class NonEmptyCrapCheckResult implements CrapCheckResult
{
    /** @param array<Method> $tooCrappyMethods */
    public function __construct(
        public array $tooCrappyMethods
    )
    {
    }
}