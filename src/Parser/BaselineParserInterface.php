<?php

namespace Leovie\PhpunitCrapCheck\Parser;

use Leovie\PhpunitCrapCheck\DTO\Baseline;

interface BaselineParserInterface
{
    public function parse(string $baselineContent): Baseline;
}