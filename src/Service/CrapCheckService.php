<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Service;

use Leovie\PhpunitCrapCheck\DTO\CrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\Parser\CloverParserInterface;

class CrapCheckService
{
    public function __construct(
        private readonly CloverParserInterface $cloverParser
    )
    {
    }

    public function check(string $cloverReportContent, int $threshold): CrapCheckResult
    {
        $tooCrappyMethods = array_values(array_filter(
            $this->cloverParser->parseMethods($cloverReportContent),
            fn (Method $method): bool => $method->crap > $threshold,
        ));

        if (count($tooCrappyMethods) === 0) {
            return new EmptyCrapCheckResult();
        }

        return new NonEmptyCrapCheckResult($tooCrappyMethods);
    }
}