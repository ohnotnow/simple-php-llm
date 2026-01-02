<?php

declare(strict_types=1);

namespace Ohffs\SimpleLlm\Providers;

use Ohffs\SimpleLlm\Response;
use Ohffs\SimpleLlm\Usage;
use Ohffs\SimpleLlm\LlmException;

class AnthropicProvider implements ProviderInterface
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    public function send(array $messages, string $model, int $maxTokens = self::DEFAULT_MAX_TOKENS): Response
    {
        $apiKey = getenv('ANTHROPIC_API_KEY');
        if ($apiKey === false || $apiKey === '') {
            throw new \RuntimeException('ANTHROPIC_API_KEY environment variable is not set');
        }

        // Extract system message if present
        $systemContent = null;
        $filteredMessages = [];
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemContent = $message['content'];
            } else {
                $filteredMessages[] = $message;
            }
        }

        $body = [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'messages' => $filteredMessages,
        ];

        if ($systemContent !== null) {
            $body['system'] = $systemContent;
        }

        $response = fetch(self::ENDPOINT, [
            'method' => 'POST',
            'headers' => [
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'json' => $body,
        ]);

        $data = $response->json();

        if (! $response->successful()) {
            throw new LlmException(
                statusCode: $response->status(),
                responseBody: $data,
                message: $data['error']['message'] ?? 'Anthropic API error',
            );
        }

        return new Response(
            textContent: $data['content'][0]['text'],
            usage: new Usage(
                inputTokens: $data['usage']['input_tokens'],
                outputTokens: $data['usage']['output_tokens'],
            ),
            model: $data['model'],
        );
    }
}
