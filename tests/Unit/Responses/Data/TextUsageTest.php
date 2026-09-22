<?php

use Laravel\Ai\Responses\Data\TextUsage;

test('usage defaults to zero tokens and unreported details', function (): void {
    $usage = new TextUsage;

    expect($usage->inputTokens)->toBe(0)
        ->and($usage->outputTokens)->toBe(0)
        ->and($usage->cacheReadInputTokens)->toBeNull()
        ->and($usage->cacheWriteInputTokens)->toBeNull()
        ->and($usage->reasoningTokens)->toBeNull();
});

test('usage derives totals from the inclusive input and output counts', function (): void {
    $usage = new TextUsage(100, 50, cacheReadInputTokens: 30, cacheWriteInputTokens: 20, reasoningTokens: 5);

    expect($usage->totalTokens())->toBe(150)
        ->and($usage->uncachedInputTokens())->toBe(50);
});

test('usage treats unreported cache counts as zero when deriving the uncached input', function (): void {
    expect((new TextUsage(100, 50))->uncachedInputTokens())->toBe(100);
});

test('usage add sums every count', function (): void {
    $combined = (new TextUsage(100, 50, 10, 25, 5))->add(new TextUsage(50, 25, 5, 10, 0));

    expect($combined)->toEqual(new TextUsage(150, 75, 15, 35, 5));
});

test('usage add keeps a detail null only when neither side reported it', function (): void {
    $combined = (new TextUsage(1, 1, cacheReadInputTokens: 7))->add(new TextUsage(1, 1, reasoningTokens: 3));

    expect($combined->cacheReadInputTokens)->toBe(7)
        ->and($combined->reasoningTokens)->toBe(3)
        ->and($combined->cacheWriteInputTokens)->toBeNull();
});

test('usage from array restores what to array serialized', function (): void {
    $usage = new TextUsage(100, 50, 10, 25, null);

    expect(TextUsage::fromArray($usage->toArray()))->toEqual($usage)
        ->and(TextUsage::fromArray([]))->toEqual(new TextUsage);
});

test('usage to array serializes every count', function (): void {
    $usage = new TextUsage(100, 50, 10, 25, null);

    expect($usage->toArray())->toBe([
        'input_tokens' => 100,
        'output_tokens' => 50,
        'cost' => null,
        'cache_read_input_tokens' => 10,
        'cache_write_input_tokens' => 25,
        'reasoning_tokens' => null,
    ])->and($usage->jsonSerialize())->toBe($usage->toArray());
});

test('usage defaults the cost to unreported', function (): void {
    expect((new TextUsage(100, 50))->cost)->toBeNull();
});

test('usage add sums the reported costs', function (): void {
    $combined = (new TextUsage(100, 50, cost: 0.0025))->add(new TextUsage(50, 25, cost: 0.0011));

    expect($combined->cost)->toBe(0.0036);
});

test('usage add keeps the cost null only when neither side reported it', function (): void {
    expect((new TextUsage(1, 1, cost: 0.5))->add(new TextUsage(1, 1))->cost)->toBe(0.5)
        ->and((new TextUsage(1, 1))->add(new TextUsage(1, 1))->cost)->toBeNull();
});

test('usage round trips the cost through to array and from array', function (): void {
    $usage = new TextUsage(100, 50, 10, 25, null, 0.0042);

    expect($usage->toArray()['cost'])->toBe(0.0042)
        ->and(TextUsage::fromArray($usage->toArray()))->toEqual($usage);
});
