<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Tests\Unit\Parser;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\Parser\BaselineParser;
use PHPUnit\Framework\TestCase;

final class BaselineParserTest extends TestCase
{
    /** @dataProvider parseProvider */
    public function testParse(Baseline $expected, string $baselineContent): void
    {
        self::assertEquals($expected, (new BaselineParser())->parse($baselineContent));
    }

    public static function parseProvider(): array
    {
        return [
            'empty baseline' => [
                'expected' => new Baseline(new EmptyCrapCheckResult()),
                'baselineContent' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/baseline_empty.json')
            ],
            'non empty baseline' => [
                'expected' => new Baseline(new NonEmptyCrapCheckResult([
                    new Method('ClassA', 'm1', 10),
                    new Method('Foo\\ClassB', 'm2', 2),
                ])),
                'baselineContent' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/baseline.json')
            ],
        ];
    }
}
