<?php

declare(strict_types=1);

namespace Ohffs\SimpleLlm;

class LlmException extends \Exception
{
    public function __construct(
        public readonly int $statusCode,
        public readonly array $responseBody,
        string $message,
    ) {
        parent::__construct($message);
    }
}
