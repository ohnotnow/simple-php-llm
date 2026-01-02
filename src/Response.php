<?php

declare(strict_types=1);

namespace Ohffs\SimpleLlm;

class Response
{
    public function __construct(
        public readonly string $textContent,
        public readonly Usage $usage,
        public readonly string $model,
    ) {}
}
