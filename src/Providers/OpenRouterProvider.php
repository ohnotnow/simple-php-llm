<?php

declare(strict_types=1);

namespace Ohffs\SimpleLlm\Providers;

use Ohffs\SimpleLlm\Response;
use Ohffs\SimpleLlm\Usage;
use Ohffs\SimpleLlm\LlmException;

class OpenRouterProvider implements ProviderInterface
{
    private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    public function send(array $messages, string $model, int $maxTokens = self::DEFAULT_MAX_TOKENS): Response
    {
        $apiKey = getenv('OPENROUTER_API_KEY');
        if ($apiKey === false || $apiKey === '') {
            throw new \RuntimeException('OPENROUTER_API_KEY environment variable is not set');
        }

        $body = [
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $maxTokens,
        ];

        $response = fetch(self::ENDPOINT, [
            'method' => 'POST',
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);

        $data = $response->json();

        if (! $response->successful()) {
            throw new LlmException(
                statusCode: $response->status(),
                responseBody: $data,
                message: $data['error']['message'] ?? 'OpenRouter API error',
            );
        }

        // OpenRouter uses prompt_tokens/completion_tokens, normalize to inputTokens/outputTokens
        return new Response(
            textContent: $data['choices'][0]['message']['content'],
            usage: new Usage(
                inputTokens: $data['usage']['prompt_tokens'],
                outputTokens: $data['usage']['completion_tokens'],
            ),
            model: $data['model'],
        );
    }
}
