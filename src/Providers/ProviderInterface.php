<?php

declare(strict_types=1);

namespace Ohffs\SimpleLlm\Providers;

use Ohffs\SimpleLlm\Response;

interface ProviderInterface
{
    public const DEFAULT_MAX_TOKENS = 128000;

    /**
     * @param array<array{role: string, content: string}> $messages
     */
    public function send(array $messages, string $model, int $maxTokens = self::DEFAULT_MAX_TOKENS): Response;
}
