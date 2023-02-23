<?php

namespace Leovie\PhpunitCrapCheck\Generator;

use Leovie\PhpunitCrapCheck\DTO\Baseline;

interface BaselineOutputGeneratorInterface
{
    public function generate(Baseline $baseline): string;
}