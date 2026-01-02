<?php

declare(strict_types=1);

namespace Ohffs\SimpleLlm;

class Usage
{
    public function __construct(
        public readonly int $inputTokens,
        public readonly int $outputTokens,
    ) {}
}
