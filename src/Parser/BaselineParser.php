<?php

namespace Leovie\PhpunitCrapCheck\Parser;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\DTO\EmptyCrapCheckResult;
use Leovie\PhpunitCrapCheck\DTO\Method;
use Leovie\PhpunitCrapCheck\DTO\NonEmptyCrapCheckResult;
use Override;

/** @phpstan-type BaselineData array<
 *   array{
 *     classFQN: string,
 *     name: string,
 *     crap: int,
 *   }
 * >
 */
final readonly class BaselineParser implements BaselineParserInterface
{
    #[Override]
    public function parse(string $baselineContent): Baseline
    {
        /** @var BaselineData $baselineData */
        $baselineData = \Safe\json_decode($baselineContent, true);

        if (empty($baselineData)) {
            return new Baseline(new EmptyCrapCheckResult());
        }

        return new Baseline(
            new NonEmptyCrapCheckResult(array_map(
                fn(array $methodData): Method => new Method(
                    $methodData['classFQN'],
                    $methodData['name'],
                    $methodData['crap'],
                ),
                $baselineData
            ))
        );
    }
}