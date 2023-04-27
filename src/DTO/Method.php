<?php

declare(strict_types=1);

namespace Leovie\PhpunitCrapCheck\DTO;

class Method
{
    public function __construct(
        public readonly string $classFQN,
        public readonly string $name,
        public readonly int    $crap,
    )
    {
    }
}