<?php

namespace Tests\Unit\Transcribers;

use Mockery;
use Pest\Expectation;
use Mockery\MockInterface;
use App\Syllaby\Generators\DTOs\CaptionResponse;
use App\Syllaby\Generators\Vendors\Transcribers\Whisper;

it('successfully transcribes an audio file using Whisper', function () {
    $whisper = $this->instance(Whisper::class, Mockery::mock(Whisper::class, function (MockInterface $mock) {
        $response = CaptionResponse::fromWhisper([
            'text' => 'Foo Bar Baz',
            'chunks' => [
                ['text' => 'Foo', 'timestamp' => [0.00, 0.00]],
                ['text' => 'Bar', 'timestamp' => [0.10, 0.15]],
                ['text' => 'Baz', 'timestamp' => [0.20, 0.28]],
            ]]);

        $mock->shouldReceive('captions')->once()->andReturn($response);
    }));

    $transcription = $whisper->captions('url', ['language' => 'en']);

    expect($transcription)->toBeInstanceOf(CaptionResponse::class)
        ->and($transcription->captions['words'])
        ->toHaveCount(3)->sequence(
            fn (Expectation $word) => expect($word->value)->toHaveKey('text', 'Foo'),
            fn (Expectation $word) => expect($word->value)->toHaveKey('text', 'Bar'),
            fn (Expectation $word) => expect($word->value)->toHaveKey('text', 'Baz'),
        );
});

it('fails to transcribe an audio file using Whisper', function () {
    $whisper = $this->instance(Whisper::class, Mockery::mock(Whisper::class, function (MockInterface $mock) {
        $mock->shouldReceive('captions')->once()->andReturnNull();
    }));

    $captions = $whisper->captions('url', ['language' => 'en']);

    expect($captions)->toBeNull();
});
