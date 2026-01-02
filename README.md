# SimpleLlm

A simple PHP library for LLM API interactions. The 90% use case: send messages, get a response.

No streaming. No tool calls. No agents, tool calls, multi-modal. Just the basics.

## Installation

```bash
composer require ohnotnow/simple-php-llm
```

## Quick Start

```php
use Ohffs\SimpleLlm\Client;

$client = new Client(provider: 'anthropic', model: 'claude-4.5-sonnet');

$response = $client->send([
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What is the capital of Scotland?'],
]);

echo $response->textContent;
// "The capital of Scotland is Edinburgh."

echo $response->usage->inputTokens;  // 25
echo $response->usage->outputTokens; // 12
echo $response->model;               // "claude-4.5-sonnet"
```

## Supported Providers

| Provider | Environment Variable | Example Model |
|----------|---------------------|---------------|
| Anthropic | `ANTHROPIC_API_KEY` | `claude-sonnet-4-20250514` |
| OpenAI | `OPENAI_API_KEY` | `gpt-4o` |
| OpenRouter | `OPENROUTER_API_KEY` | `anthropic/claude-3.5-sonnet` |

## Constructor Styles

```php
// Explicit provider and model
$client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

// Litellm-style shorthand
$client = new Client(from: 'anthropic/claude-sonnet-4-20250514');
```

## Max Tokens

The default `max_tokens` is set to 128,000 - because it's not 2023 anymore and tokens are cheap. If you need to limit output for a specific request:

```php
$response = $client->send($messages, maxTokens: 1024);
```

## Error Handling

API errors throw `LlmException` with the status code and response body:

```php
use Ohffs\SimpleLlm\LlmException;

try {
    $response = $client->send($messages);
} catch (LlmException $e) {
    echo $e->getMessage();      // "Invalid API key"
    echo $e->statusCode;        // 401
    print_r($e->responseBody);  // Full API error response
}
```

## Testing with Fakes

Built-in fake support means no HTTP mocking libraries needed:

```php
use Ohffs\SimpleLlm\Client;
use Ohffs\SimpleLlm\Response;
use Ohffs\SimpleLlm\Usage;

$client = new Client(provider: 'anthropic', model: 'claude-4.5-sonnet');

$client->fake([
    new Response(
        textContent: 'Edinburgh is the capital of Scotland, sadly.',
        usage: new Usage(inputTokens: 25, outputTokens: 12),
        model: 'claude-4.5-sonnet',
    ),
]);

// Now send() returns the fake response instead of hitting the API
$response = $client->send([
    ['role' => 'user', 'content' => 'What is the capital of Scotland?'],
]);

echo $response->textContent; // "Edinburgh is the capital of Scotland, sadly."
```

### Sequential Fakes

Fakes are returned in order:

```php
$client->fake([
    new Response(textContent: 'First response', usage: new Usage(10, 5), model: 'claude-4.5-sonnet'),
    new Response(textContent: 'Second response', usage: new Usage(15, 8), model: 'claude-4.5-sonnet'),
]);

$client->send([...]); // Returns "First response"
$client->send([...]); // Returns "Second response"
$client->send([...]); // Throws RuntimeException - no more fakes
```

### Assertions

```php
// Assert a specific request was sent
$client->assertSent(function (array $messages, Response $response) {
    return str_contains($messages[0]['content'], 'Scotland');
});

// Assert number of requests
$client->assertSentCount(2);

// Get all recorded requests for manual inspection
$recorded = $client->getRecorded();
// [
//     ['messages' => [...], 'response' => Response, 'maxTokens' => 128000],
//     ['messages' => [...], 'response' => Response, 'maxTokens' => 128000],
// ]
```

## Response Object

```php
$response->textContent;           // string - The response text
$response->usage->inputTokens;    // int - Tokens in the prompt
$response->usage->outputTokens;   // int - Tokens in the response
$response->model;                 // string - Model that generated the response
```

## Requirements

- PHP 8.3+
- One of: `ANTHROPIC_API_KEY`, `OPENAI_API_KEY`, or `OPENROUTER_API_KEY`

## Contributing

Contributions are welcome. Please open an issue first to discuss what you'd like to change.

```bash
git clone https://github.com/ohnotnow/simple-php-llm.git
cd simple-php-llm
composer install
./vendor/bin/pest
```

## License

[MIT](LICENSE)
