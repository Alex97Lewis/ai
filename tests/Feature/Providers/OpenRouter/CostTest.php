<?php

use Illuminate\Support\Facades\Http;
use Laravel\Ai\Ai;
use Laravel\Ai\Image;
use Laravel\Ai\Reranking;

use function Laravel\Ai\agent;

beforeEach(function (): void {
    config(['ai.providers.openrouter' => [
        ...config('ai.providers.openrouter'),
        'key' => 'test-key',
    ]]);
});

test('text response reports the billed cost', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'chatcmpl-123',
        'model' => 'anthropic/claude-sonnet-4.6',
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => 'Hello'],
            'finish_reason' => 'stop',
        ]],
        'usage' => [
            'prompt_tokens' => 100,
            'completion_tokens' => 50,
            'cost' => 0.00123,
        ],
    ])]);

    $response = agent()->prompt('Hello', provider: 'openrouter');

    expect($response->usage->cost)->toBe(0.00123);
});

test('text response leaves the cost unreported when the provider omits it', function (): void {
    Http::fake(['*' => Http::response([
        'id' => 'chatcmpl-123',
        'model' => 'anthropic/claude-sonnet-4.6',
        'choices' => [[
            'index' => 0,
            'message' => ['role' => 'assistant', 'content' => 'Hello'],
            'finish_reason' => 'stop',
        ]],
        'usage' => ['prompt_tokens' => 100, 'completion_tokens' => 50],
    ])]);

    $response = agent()->prompt('Hello', provider: 'openrouter');

    expect($response->usage->cost)->toBeNull();
});

test('embeddings response reports the billed cost', function (): void {
    Http::fake(['*' => Http::response([
        'object' => 'list',
        'data' => [['object' => 'embedding', 'index' => 0, 'embedding' => [0.1, 0.2]]],
        'usage' => ['prompt_tokens' => 10, 'cost' => 0.000004],
    ])]);

    $response = Ai::instance('openrouter')->embeddings(['Hello']);

    expect($response->usage->inputTokens)->toBe(10)
        ->and($response->usage->cost)->toBe(0.000004);
});

test('reranking response reports the billed cost', function (): void {
    Http::fake(['*' => Http::response([
        'results' => [['index' => 0, 'relevance_score' => 0.95]],
        'usage' => ['total_tokens' => 320, 'search_units' => 1, 'cost' => 0.0021],
    ])]);

    $response = Reranking::of(['Doc A'])->rerank('query', provider: 'openrouter', model: 'cohere/rerank-v3.5');

    expect($response->usage->searchUnits)->toBe(1.0)
        ->and($response->usage->cost)->toBe(0.0021);
});

test('image response reports the billed cost', function (): void {
    Http::fake(['openrouter.ai/*' => Http::response([
        'id' => 'chatcmpl-123',
        'model' => 'google/gemini-2.5-flash-image',
        'choices' => [[
            'index' => 0,
            'message' => [
                'role' => 'assistant',
                'content' => '',
                'images' => [[
                    'type' => 'image_url',
                    'image_url' => ['url' => 'data:image/png;base64,'.base64_encode('fake-image')],
                ]],
            ],
            'finish_reason' => 'stop',
        ]],
        'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 20, 'cost' => 0.0195],
    ])]);

    $response = Image::of('A blue circle')->generate(provider: 'openrouter', model: 'google/gemini-2.5-flash-image');

    expect($response->usage->outputTokens)->toBe(20)
        ->and($response->usage->cost)->toBe(0.0195);
});
