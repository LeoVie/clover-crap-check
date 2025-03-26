<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Parser;

use Leovie\PhpunitCrapCheck\DTO\Method;
use Symfony\Component\DomCrawler\Crawler;

final readonly class CloverParser implements CloverParserInterface
{
    #[\Override]
    public function parseMethods(string $cloverReportContent): array
    {
        $crawler = new Crawler($cloverReportContent);
        $rawMethods = $crawler->filter('line[type="method"]');

        /** @var array<Method> $methods */
        $methods = $rawMethods->each(function (Crawler $node): Method {
            $firstClassName = $node->siblings()->filter('class')->first()->attr('name');
            $name = $node->attr('name');
            $crap = $node->attr('crap');

            return new Method(
                $firstClassName === null ? '' : $firstClassName,
                $name === null ? '' : $name,
                $crap === null ? 0 : (int)$crap,
            );
        });

        return $methods;
    }
}