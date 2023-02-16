<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\Service;

use Leovie\PhpunitCrapCheck\DTO\Baseline;
use Leovie\PhpunitCrapCheck\Generator\BaselineOutputGenerator;

readonly class BaselineOutputService
{
    public function __construct(
        private BaselineOutputGenerator $baselineOutputGenerator
    )
    {
    }

    public function save(Baseline $baseline, string $filepath): void
    {
        \Safe\file_put_contents($filepath, $this->baselineOutputGenerator->generate($baseline));
    }
}