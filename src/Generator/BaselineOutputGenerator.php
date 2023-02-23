<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Generator;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;

readonly class BaselineOutputGenerator implements BaselineOutputGeneratorInterface
{
    public function generate(Baseline $baseline): string
    {
        $baselineCrapCheckResult = $baseline->crapCheckResult;
        if ($baselineCrapCheckResult instanceof EmptyCrapCheckResult) {
            return \Safe\json_encode([]);
        }

        /** @var NonEmptyCrapCheckResult $baselineCrapCheckResult */

        return \Safe\json_encode($baselineCrapCheckResult->tooCrappyMethods, JSON_PRETTY_PRINT);
    }
}