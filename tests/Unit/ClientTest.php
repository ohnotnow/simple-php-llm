<?php

use Ohffs\SimpleLlm\Client;
use Ohffs\SimpleLlm\Response;
use Ohffs\SimpleLlm\Usage;

test('client can be constructed with provider and model', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    expect($client)->toBeInstanceOf(Client::class);
});

test('client can be constructed with from parameter', function () {
    $client = new Client(from: 'anthropic/claude-sonnet-4-20250514');

    expect($client)->toBeInstanceOf(Client::class);
});

test('client throws exception when neither provider/model nor from is provided', function () {
    new Client();
})->throws(InvalidArgumentException::class);

test('client throws exception when only provider is provided', function () {
    new Client(provider: 'anthropic');
})->throws(InvalidArgumentException::class);

test('client throws exception when only model is provided', function () {
    new Client(model: 'claude-sonnet-4-20250514');
})->throws(InvalidArgumentException::class);

test('client throws exception for invalid from format', function () {
    new Client(from: 'invalid-format');
})->throws(InvalidArgumentException::class);

test('client can use fake responses', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(
            textContent: 'Hello!',
            usage: new Usage(10, 5),
            model: 'claude-sonnet-4-20250514',
        ),
    ]);

    $response = $client->send([
        ['role' => 'user', 'content' => 'Hi'],
    ]);

    expect($response->textContent)->toBe('Hello!');
    expect($response->usage->inputTokens)->toBe(10);
    expect($response->usage->outputTokens)->toBe(5);
});

test('client returns fake responses in sequence', function () {
    $client = new Client(from: 'openai/gpt-4');

    $client->fake([
        new Response(textContent: 'First', usage: new Usage(10, 5), model: 'gpt-4'),
        new Response(textContent: 'Second', usage: new Usage(15, 8), model: 'gpt-4'),
    ]);

    $first = $client->send([['role' => 'user', 'content' => 'One']]);
    $second = $client->send([['role' => 'user', 'content' => 'Two']]);

    expect($first->textContent)->toBe('First');
    expect($second->textContent)->toBe('Second');
});

test('client throws exception when fake responses are exhausted', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'Only one', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([['role' => 'user', 'content' => 'First']]);
    $client->send([['role' => 'user', 'content' => 'Second']]);
})->throws(RuntimeException::class, 'No more fake responses available');

test('client records sent messages with assertSent', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'Response', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([
        ['role' => 'user', 'content' => 'Hello there'],
    ]);

    $client->assertSent(function ($messages, $response) {
        return $messages[0]['content'] === 'Hello there';
    });

    expect(true)->toBeTrue(); // Explicit assertion to avoid risky test warning
});

test('client assertSent throws when no matching request found', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'Response', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([
        ['role' => 'user', 'content' => 'Hello'],
    ]);

    $client->assertSent(function ($messages, $response) {
        return $messages[0]['content'] === 'Goodbye';
    });
})->throws(AssertionError::class);

test('client assertSentCount validates number of requests', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'One', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
        new Response(textContent: 'Two', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([['role' => 'user', 'content' => 'First']]);
    $client->send([['role' => 'user', 'content' => 'Second']]);

    $client->assertSentCount(2);

    expect(true)->toBeTrue(); // Explicit assertion to avoid risky test warning
});

test('client assertSentCount throws on mismatch', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'One', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([['role' => 'user', 'content' => 'Only one']]);

    $client->assertSentCount(5);
})->throws(AssertionError::class);

test('client getRecorded returns all recorded requests', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'One', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
        new Response(textContent: 'Two', usage: new Usage(15, 8), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([['role' => 'user', 'content' => 'First']]);
    $client->send([['role' => 'user', 'content' => 'Second']]);

    $recorded = $client->getRecorded();

    expect($recorded)->toHaveCount(2);
    expect($recorded[0]['messages'][0]['content'])->toBe('First');
    expect($recorded[1]['messages'][0]['content'])->toBe('Second');
});

test('client uses default maxTokens of 128000', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'Response', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([['role' => 'user', 'content' => 'Hello']]);

    $recorded = $client->getRecorded();

    expect($recorded[0]['maxTokens'])->toBe(128000);
});

test('client accepts custom maxTokens parameter', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'Response', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([['role' => 'user', 'content' => 'Hello']], maxTokens: 1024);

    $recorded = $client->getRecorded();

    expect($recorded[0]['maxTokens'])->toBe(1024);
});

test('client records different maxTokens for each request', function () {
    $client = new Client(provider: 'anthropic', model: 'claude-sonnet-4-20250514');

    $client->fake([
        new Response(textContent: 'One', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
        new Response(textContent: 'Two', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
        new Response(textContent: 'Three', usage: new Usage(10, 5), model: 'claude-sonnet-4-20250514'),
    ]);

    $client->send([['role' => 'user', 'content' => 'First']]);  // default 128000
    $client->send([['role' => 'user', 'content' => 'Second']], maxTokens: 4096);
    $client->send([['role' => 'user', 'content' => 'Third']], maxTokens: 256);

    $recorded = $client->getRecorded();

    expect($recorded[0]['maxTokens'])->toBe(128000);
    expect($recorded[1]['maxTokens'])->toBe(4096);
    expect($recorded[2]['maxTokens'])->toBe(256);
});
