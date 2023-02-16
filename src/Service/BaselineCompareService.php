<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Service;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\DTO\BaselineCompareResult;
use Leovie\PhpunitCrapCheck\DTO\BaselineDiffersResult;
use Leovie\PhpunitCrapCheck\DTO\BaselineEqualsResult;
use Leovie\PhpunitCrapCheck\DTO\CrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;

readonly class BaselineCompareService
{
    public function compare(CrapCheckResult $crapCheckResult, Baseline $baseline): BaselineCompareResult
    {
        if ($crapCheckResult == $baseline->crapCheckResult) {
            return new BaselineEqualsResult();
        }

        if ($crapCheckResult instanceof EmptyCrapCheckResult) {
            /** @var NonEmptyCrapCheckResult $baselineCrapCheckResult */
            $baselineCrapCheckResult = $baseline->crapCheckResult;

            return new BaselineDiffersResult(
                methodsNotOccurringAnymore: $baselineCrapCheckResult->tooCrappyMethods,
                methodsNewlyOccurring: [],
                methodsGotCrappier: [],
                methodsGotLessCrappy: [],
            );
        }

        /** @var NonEmptyCrapCheckResult $crapCheckResult */
        if ($baseline->crapCheckResult instanceof EmptyCrapCheckResult) {
            return new BaselineDiffersResult(
                methodsNotOccurringAnymore: [],
                methodsNewlyOccurring: $crapCheckResult->tooCrappyMethods,
                methodsGotCrappier: [],
                methodsGotLessCrappy: [],
            );
        }

        /** @var NonEmptyCrapCheckResult $baselineCrapCheckResult */
        $baselineCrapCheckResult = $baseline->crapCheckResult;

        $methodsNotOccurringAnymore = [];
        $methodsNewlyOccurring = [];
        $methodsGotCrappier = [];
        $methodsGotLessCrappy = [];
        foreach ($baselineCrapCheckResult->tooCrappyMethods as $baselineMethod) {
            $actualMethod = $this->extractByClassFQNAndMethod(
                $crapCheckResult,
                $baselineMethod->classFQN,
                $baselineMethod->name
            );

            if ($actualMethod === null) {
                $methodsNotOccurringAnymore[] = $baselineMethod;
            } else if ($this->isCrappier($actualMethod, $baselineMethod)) {
                $methodsGotCrappier[] = $actualMethod;
            } else if ($this->isLessCrappy($actualMethod, $baselineMethod)) {
                $methodsGotLessCrappy[] = $actualMethod;
            }
        }

        foreach ($crapCheckResult->tooCrappyMethods as $actualMethod) {
            $baselineMethod = $this->extractByClassFQNAndMethod(
                $baselineCrapCheckResult,
                $actualMethod->classFQN,
                $actualMethod->name
            );
            if ($baselineMethod === null) {
                $methodsNewlyOccurring[] = $actualMethod;
            }
        }

        return new BaselineDiffersResult(
            methodsNotOccurringAnymore: $methodsNotOccurringAnymore,
            methodsNewlyOccurring: $methodsNewlyOccurring,
            methodsGotCrappier: $methodsGotCrappier,
            methodsGotLessCrappy: $methodsGotLessCrappy,
        );
    }

    private function extractByClassFQNAndMethod(
        NonEmptyCrapCheckResult $crapCheckResult,
        string                  $classFQN,
        string                  $name
    ): ?Method
    {
        foreach ($crapCheckResult->tooCrappyMethods as $method) {
            if ($method->classFQN === $classFQN && $method->name === $name) {
                return $method;
            }
        }

        return null;
    }

    private function isCrappier(Method $methodA, Method $methodB): bool
    {
        return $methodA->crap > $methodB->crap;
    }

    private function isLessCrappy(Method $methodA, Method $methodB): bool
    {
        return $methodA->crap < $methodB->crap;
    }
}