<?php

namespace Leovie\PhpunitCrapCheck\Parser;

use Leovie\PhpunitCrapCheck\DTO\Method;

interface CloverParserInterface
{
    /**
     * @return array<Method>
     * @throws \Exception
     */
    public function parseMethods(string $cloverReportContent): array;
}