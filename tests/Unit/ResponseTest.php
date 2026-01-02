<?php

use Ohffs\SimpleLlm\Response;
use Ohffs\SimpleLlm\Usage;

test('response stores text content, usage, and model', function () {
    $usage = new Usage(inputTokens: 100, outputTokens: 50);
    $response = new Response(
        textContent: 'Hello, world!',
        usage: $usage,
        model: 'claude-sonnet-4-20250514',
    );

    expect($response->textContent)->toBe('Hello, world!');
    expect($response->usage)->toBe($usage);
    expect($response->model)->toBe('claude-sonnet-4-20250514');
});
