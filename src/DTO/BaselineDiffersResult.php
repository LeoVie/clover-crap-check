<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

class BaselineDiffersResult implements BaselineCompareResult
{
    /**
     * @param array<Method> $methodsNotOccurringAnymore
     * @param array<Method> $methodsNewlyOccurring
     * @param array<Method> $methodsGotCrappier
     * @param array<Method> $methodsGotLessCrappy
     */
    public function __construct(
        public readonly array $methodsNotOccurringAnymore,
        public readonly array $methodsNewlyOccurring,
        public readonly array $methodsGotCrappier,
        public readonly array $methodsGotLessCrappy
    )
    {
    }
}