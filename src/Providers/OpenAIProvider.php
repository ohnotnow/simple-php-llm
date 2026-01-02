<?php

declare(strict_types=1);

namespace Ohffs\SimpleLlm\Providers;

use Ohffs\SimpleLlm\Response;
use Ohffs\SimpleLlm\Usage;
use Ohffs\SimpleLlm\LlmException;

class OpenAIProvider implements ProviderInterface
{
    private const ENDPOINT = 'https://api.openai.com/v1/responses';

    public function send(array $messages, string $model, int $maxTokens = self::DEFAULT_MAX_TOKENS): Response
    {
        $apiKey = getenv('OPENAI_API_KEY');
        if ($apiKey === false || $apiKey === '') {
            throw new \RuntimeException('OPENAI_API_KEY environment variable is not set');
        }

        $body = [
            'model' => $model,
            'input' => $messages,
            'max_output_tokens' => $maxTokens,
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
                message: $data['error']['message'] ?? 'OpenAI API error',
            );
        }

        // Find the message output item
        $textContent = '';
        foreach ($data['output'] as $item) {
            if ($item['type'] === 'message') {
                $textContent = $item['content'][0]['text'] ?? '';
                break;
            }
        }

        return new Response(
            textContent: $textContent,
            usage: new Usage(
                inputTokens: $data['usage']['input_tokens'],
                outputTokens: $data['usage']['output_tokens'],
            ),
            model: $data['model'],
        );
    }
}
