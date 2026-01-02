<?php

use Ohffs\SimpleLlm\Usage;

test('usage stores input and output tokens', function () {
    $usage = new Usage(inputTokens: 100, outputTokens: 50);

    expect($usage->inputTokens)->toBe(100);
    expect($usage->outputTokens)->toBe(50);
});
