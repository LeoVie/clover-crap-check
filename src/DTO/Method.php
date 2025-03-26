<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

final readonly class Method
{
    public function __construct(
        public string $classFQN,
        public string $name,
        public int    $crap,
    )
    {
    }
}