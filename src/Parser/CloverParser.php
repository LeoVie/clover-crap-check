<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Parser;

use Leovie\PhpunitCrapCheck\DTO\Method;
use Symfony\Component\DomCrawler\Crawler;

readonly class CloverParser implements CloverParserInterface
{
    /** @return array<Method> */
    public function parseMethods(string $cloverReportContent): array
    {
        $crawler = new Crawler($cloverReportContent);
        $rawMethods = $crawler->filter('line[type="method"]');

        return $rawMethods->each(fn (Crawler $node) => new Method(
            $node->siblings()->filter('class')->first()->attr('name') ?: '',
            $node->attr('name') ?: '',
            (int) $node->attr('crap')
        ));
    }
}