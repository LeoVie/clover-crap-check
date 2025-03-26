<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Tests\Unit\Parser;

use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\Parser\CloverParser;
use PHPUnit\Framework\TestCase;

final class CloverParserTest extends TestCase
{
    /** @dataProvider getMethodsProvider */
    public function testGetMethods(array $expected, string $cloverReportContent): void
    {
        self::assertEquals($expected, (new CloverParser())->parseMethods($cloverReportContent));
    }

    public static function getMethodsProvider(): array
    {
        return [
            'valid clover report' => [
                'expected' => [
                    new Method('ClassA', 'm1', 10),
                    new Method('Foo\\ClassB', 'm2', 2),
                ],
                'cloverReportContent' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/clover.xml'),
            ],
            'method has no crap' => [
                'expected' => [
                    new Method('ClassA', 'm1', 0),
                ],
                'cloverReportContent' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/clover_methodHasNoCrap.xml'),
            ],
            'empty report' => [
                'expected' => [],
                'cloverReportContent' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/clover_empty.xml'),
            ],
            'empty string' => [
                'expected' => [],
                'cloverReportContent' => '',
            ],
        ];
    }
}