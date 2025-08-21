<?php

namespace Tests\Unit\Speech;

use Exception;
use App\Syllaby\Speeches\Transformers\TextTransformer;

beforeEach(function () {
    $this->transformer = new TextTransformer;
});

test('elevenlabs: it formats text for elevenlabs', function () {
    $text = 'Hello [pause 1s] world [pause 0.5s] test [pause 500ms]';
    $expected = 'Hello <break time="1s" /> world <break time="0.5s" /> test <break time="500ms" />';

    $result = $this->transformer->format($text, 'elevenlabs');

    expect($result)->toBe($expected);
});

test('elevenlabs: handles case insensitive pause tags', function () {
    $text = 'Test [PAUSE 1s] [Pause 2s] [pause 3s]';
    $expected = 'Test <break time="1s" /> <break time="2s" /> <break time="3s" />';

    $result = $this->transformer->format($text, 'elevenlabs');

    expect($result)->toBe($expected);
});

test('elevenlabs: it handles different time formats', function () {
    $text = 'Test [pause 1.5s] [pause 500ms] [pause 0.7s]';
    $expected = 'Test <break time="1.5s" /> <break time="500ms" /> <break time="0.7s" />';

    $result = $this->transformer->format($text, 'elevenlabs');

    expect($result)->toBe($expected);
});

test('elevenlabs: it throws exception for invalid provider', function () {
    expect(fn () => $this->transformer->format('Some text', 'invalid_provider'))
        ->toThrow(Exception::class, 'Invalid provider');
});

test('elevenlabs: it handles text without pause tags', function () {
    $text = 'Hello world without any pauses';

    $result = $this->transformer->format($text, 'elevenlabs');

    expect($result)->toBe($text);
});
