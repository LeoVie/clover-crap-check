<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Tests\Unit\Parser;

use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\Exception\CloverReportNotParseableException;
use Leovie\PhpunitCrapCheck\Parser\CloverParser;
use PHPUnit\Framework\TestCase;

class CloverParserTest extends TestCase
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
                    new Method('Leovie\\PhpunitCrapCheck\\CloverParser', 'parseMethods', 1),
                    new Method('Leovie\\PhpunitCrapCheck\\Method', '__construct', 5),
                ],
                'cloverReportContent' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/clover.xml'),
            ],
            'empty report' => [
                'expected' => [],
                'cloverReportContent' => \Safe\file_get_contents(__DIR__ . '/../../_testdata/clover_empty.xml'),
            ],
        ];
    }

    public function testGetMethodsThrows(): void
    {
        self::expectException(CloverReportNotParseableException::class);

        (new CloverParser())->parseMethods('');
    }
}