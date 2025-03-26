<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Tests\Unit\Service;

use Leovie\PhpunitCrapCheck\DTO\CrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\Parser\CloverParserInterface;
use Leovie\PhpunitCrapCheck\Service\CrapCheckService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CrapCheckServiceTest extends TestCase
{
    #[DataProvider('checkProvider')]
    public function testCheck(CrapCheckResult $expected, array $methods, int $threshold): void
    {
        $cloverParser = $this->createMock(CloverParserInterface::class);
        /** @psalm-suppress UndefinedMethod */
        $cloverParser->method('parseMethods')->willReturn($methods);

        self::assertEquals(
            $expected,
            (new CrapCheckService($cloverParser))->check('', $threshold)
        );
    }

    public static function checkProvider(): array
    {
        $methods = [
            new Method('ClassA', 'm1', 1),
            new Method('ClassA', 'm2', 10),
            new Method('ClassA', 'm3', 5),
            new Method('ClassA', 'm4', 15),
        ];

        return [
            'no methods' => [
                'expected' => new EmptyCrapCheckResult(),
                'methods' => [],
                'threshold' => 5,
            ],
            'no too crappy methods' => [
                'expected' => new EmptyCrapCheckResult(),
                'methods' => $methods,
                'threshold' => 15,
            ],
            'too crappy methods' => [
                'expected' => new NonEmptyCrapCheckResult([
                    new Method('ClassA', 'm2', 10),
                    new Method('ClassA', 'm4', 15),
                ]),
                'methods' => $methods,
                'threshold' => 5,
            ]
        ];
    }
}