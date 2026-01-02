<?php

declare(strict_types=1);

namespace Ohffs\SimpleLlm;

use Ohffs\SimpleLlm\Providers\ProviderInterface;
use Ohffs\SimpleLlm\Providers\AnthropicProvider;
use Ohffs\SimpleLlm\Providers\OpenAIProvider;
use Ohffs\SimpleLlm\Providers\OpenRouterProvider;

class Client
{
    private string $provider;
    private string $model;
    private ?ProviderInterface $providerInstance = null;

    /** @var Response[]|null */
    private ?array $fakeResponses = null;
    private int $fakeIndex = 0;

    /** @var array<array{messages: array, response: Response, maxTokens: int}> */
    private array $recorded = [];

    public function __construct(
        ?string $provider = null,
        ?string $model = null,
        ?string $from = null,
    ) {
        if ($from !== null) {
            [$this->provider, $this->model] = $this->parseFrom($from);
        } elseif ($provider !== null && $model !== null) {
            $this->provider = $provider;
            $this->model = $model;
        } else {
            throw new \InvalidArgumentException(
                'Either provide "provider" and "model", or use "from" parameter (e.g., "anthropic/claude-sonnet-4-20250514")'
            );
        }
    }

    /**
     * @param array<array{role: string, content: string}> $messages
     */
    public function send(array $messages, ?int $maxTokens = null): Response
    {
        $maxTokens ??= ProviderInterface::DEFAULT_MAX_TOKENS;

        if ($this->fakeResponses !== null) {
            return $this->handleFakeResponse($messages, $maxTokens);
        }

        $provider = $this->getProvider();
        $response = $provider->send($messages, $this->model, $maxTokens);

        $this->recorded[] = ['messages' => $messages, 'response' => $response, 'maxTokens' => $maxTokens];

        return $response;
    }

    /**
     * @param Response[] $responses
     */
    public function fake(array $responses): void
    {
        $this->fakeResponses = $responses;
        $this->fakeIndex = 0;
        $this->recorded = [];
    }

    public function assertSent(callable $callback): void
    {
        foreach ($this->recorded as $record) {
            if ($callback($record['messages'], $record['response']) === true) {
                return;
            }
        }

        throw new \AssertionError('No matching request was sent.');
    }

    public function assertSentCount(int $expected): void
    {
        $actual = count($this->recorded);
        if ($actual !== $expected) {
            throw new \AssertionError("Expected {$expected} requests, but {$actual} were sent.");
        }
    }

    /**
     * @return array<array{messages: array, response: Response, maxTokens: int}>
     */
    public function getRecorded(): array
    {
        return $this->recorded;
    }

    /**
     * @param array<array{role: string, content: string}> $messages
     */
    private function handleFakeResponse(array $messages, int $maxTokens): Response
    {
        if ($this->fakeIndex >= count($this->fakeResponses)) {
            throw new \RuntimeException('No more fake responses available.');
        }

        $response = $this->fakeResponses[$this->fakeIndex];
        $this->fakeIndex++;

        $this->recorded[] = ['messages' => $messages, 'response' => $response, 'maxTokens' => $maxTokens];

        return $response;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseFrom(string $from): array
    {
        $parts = explode('/', $from, 2);
        if (count($parts) !== 2) {
            throw new \InvalidArgumentException(
                'Invalid "from" format. Expected "provider/model" (e.g., "anthropic/claude-sonnet-4-20250514")'
            );
        }

        return $parts;
    }

    private function getProvider(): ProviderInterface
    {
        if ($this->providerInstance !== null) {
            return $this->providerInstance;
        }

        $this->providerInstance = match ($this->provider) {
            'anthropic' => new AnthropicProvider(),
            'openai' => new OpenAIProvider(),
            'openrouter' => new OpenRouterProvider(),
            default => throw new \InvalidArgumentException("Unknown provider: {$this->provider}"),
        };

        return $this->providerInstance;
    }
}
