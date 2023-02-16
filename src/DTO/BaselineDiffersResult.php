<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

readonly class BaselineDiffersResult implements BaselineCompareResult
{
    /**
     * @param array<Method> $methodsNotOccurringAnymore
     * @param array<Method> $methodsNewlyOccurring
     * @param array<Method> $methodsGotCrappier
     * @param array<Method> $methodsGotLessCrappy
     */
    public function __construct(
        public array $methodsNotOccurringAnymore,
        public array $methodsNewlyOccurring,
        public array $methodsGotCrappier,
        public array $methodsGotLessCrappy
    )
    {
    }
}