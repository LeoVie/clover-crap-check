<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Tests\Unit\Generator;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\Generator\BaselineOutputGenerator;
use PHPUnit\Framework\TestCase;

final class BaselineOutputGeneratorTest extends TestCase
{
    /** @dataProvider generateProvider */
    public function testGenerate(string $expected, Baseline $baseline): void
    {
        self::assertJsonStringEqualsJsonString($expected, (new BaselineOutputGenerator())->generate($baseline));
    }

    public static function generateProvider(): array
    {
        return [
            'empty baseline' => [
                'expected' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/baseline_empty.json'),
                'baseline' => new Baseline(new EmptyCrapCheckResult()),
            ],
            'non empty baseline' => [
                'expected' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/baseline.json'),
                'baseline' => new Baseline(new NonEmptyCrapCheckResult([
                    new Method('ClassA', 'm1', 10),
                    new Method('Foo\\ClassB', 'm2', 2),
                ])),
            ],
        ];
    }
}