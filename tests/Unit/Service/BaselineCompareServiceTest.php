<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Tests\Unit\Service;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\DTO\BaselineCompareResult;
use Leovie\PhpunitCrapCheck\DTO\BaselineDiffersResult;
use Leovie\PhpunitCrapCheck\DTO\BaselineEqualsResult;
use Leovie\PhpunitCrapCheck\DTO\CrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\Service\BaselineCompareService;
use PHPUnit\Framework\TestCase;

final class BaselineCompareServiceTest extends TestCase
{
    /** @dataProvider compareProvider */
    public function testCompare(
        BaselineCompareResult $expected,
        CrapCheckResult       $crapCheckResult,
        Baseline              $baseline
    ): void
    {
        self::assertEquals($expected, (new BaselineCompareService())->compare($crapCheckResult, $baseline));
    }

    public static function compareProvider(): array
    {
        $m1_10 = new Method('ClassA', 'm1', 10);
        $m1_15 = new Method('ClassA', 'm1', 15);
        $m2_10 = new Method('ClassA', 'm2', 10);
        $m3_10 = new Method('ClassB', 'm3', 10);
        return [
            'both empty' => [
                'expected' => new BaselineEqualsResult(),
                'crapCheckResult' => new EmptyCrapCheckResult(),
                'baseline' => new Baseline(new EmptyCrapCheckResult()),
            ],
            'both equal' => [
                'expected' => new BaselineEqualsResult(),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_10, $m2_10]),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m1_10, $m2_10])),
            ],
            'both equal, full duplicate entry' => [
                'expected' => new BaselineEqualsResult(),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_10, $m1_10]),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m1_10, $m1_10])),
            ],
            'both equal, duplicate class method, different crap' => [
                'expected' => new BaselineEqualsResult(),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_10, $m1_15]),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m1_10, $m1_15])),
            ],
            'both equal, order differs' => [
                'expected' => new BaselineEqualsResult(),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_10, $m3_10, $m2_10]),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m2_10, $m3_10, $m1_10])),
            ],
            'actual empty' => [
                'expected' => new BaselineDiffersResult(
                    methodsNotOccurringAnymore: [$m1_10],
                    methodsNewlyOccurring: [],
                    methodsGotCrappier: [],
                    methodsGotLessCrappy: [],
                ),
                'crapCheckResult' => new EmptyCrapCheckResult(),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m1_10])),
            ],
            'baseline empty' => [
                'expected' => new BaselineDiffersResult(
                    methodsNotOccurringAnymore: [],
                    methodsNewlyOccurring: [$m1_10],
                    methodsGotCrappier: [],
                    methodsGotLessCrappy: [],
                ),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_10]),
                'baseline' => new Baseline(new EmptyCrapCheckResult()),
            ],
            'method got crappier' => [
                'expected' => new BaselineDiffersResult(
                    methodsNotOccurringAnymore: [],
                    methodsNewlyOccurring: [],
                    methodsGotCrappier: [$m1_15],
                    methodsGotLessCrappy: [],
                ),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_15]),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m1_10])),
            ],
            'method newly occurring' => [
                'expected' => new BaselineDiffersResult(
                    methodsNotOccurringAnymore: [],
                    methodsNewlyOccurring: [$m2_10],
                    methodsGotCrappier: [],
                    methodsGotLessCrappy: [],
                ),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_10, $m2_10]),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m1_10])),
            ],
            'method not occurring anymore' => [
                'expected' => new BaselineDiffersResult(
                    methodsNotOccurringAnymore: [$m2_10],
                    methodsNewlyOccurring: [],
                    methodsGotCrappier: [],
                    methodsGotLessCrappy: [],
                ),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_10]),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m1_10, $m2_10])),
            ],
            'method got less crappy' => [
                'expected' => new BaselineDiffersResult(
                    methodsNotOccurringAnymore: [],
                    methodsNewlyOccurring: [],
                    methodsGotCrappier: [],
                    methodsGotLessCrappy: [$m1_10],
                ),
                'crapCheckResult' => new NonEmptyCrapCheckResult([$m1_10]),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([$m1_15])),
            ],
        ];
    }
}