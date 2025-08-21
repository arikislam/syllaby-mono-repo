<?php

namespace App\Syllaby\Videos\Actions;

use Throwable;
use Illuminate\Support\Arr;
use App\Syllaby\Videos\Faceless;
use App\Syllaby\Videos\DTOs\Options;

class UpdateFacelessAction
{
    /**
     * @throws Throwable
     */
    public function handle(Faceless $faceless, array $input): Faceless
    {
        $options = $this->format($faceless, $input);
        $script = Arr::get($input, 'script', $faceless->script);
        $voice = Arr::get($input, 'voice_id', $faceless->voice_id);

        return tap($faceless)->update([
            'options' => $options,
            'script' => $script,
            'voice_id' => $voice,
            'is_transcribed' => blank($voice),
            'type' => Arr::get($input, 'type', $faceless->type),
            'genre_id' => Arr::get($input, 'genre_id', $faceless->genre_id),
            'character_id' => Arr::get($input, 'character_id', $faceless->character_id),
            'background_id' => Arr::get($input, 'background_id', $faceless->background_id),
            'music_id' => Arr::get($input, 'music_id', $faceless->music_id),
            'estimated_duration' => Arr::get($input, 'duration', $faceless->estimated_duration),
            'watermark_id' => Arr::get($input, 'watermark.id', $faceless->watermark_id),
        ]);
    }

    protected function format(Faceless $faceless, array $input): Options
    {
        return new Options(
            font_family: Arr::get($input, 'captions.font_family', $faceless->options->font_family),
            position: Arr::get($input, 'captions.position', $faceless->options->position),
            aspect_ratio: Arr::get($input, 'aspect_ratio', $faceless->options->aspect_ratio),
            sfx: Arr::get($input, 'sfx', $faceless->options->sfx),
            font_color: Arr::get($input, 'captions.font_color', $faceless->options->font_color),
            volume: Arr::get($input, 'volume', $faceless->options->volume),
            transition: Arr::get($input, 'transition', $faceless->options->transition),
            caption_effect: Arr::get($input, 'captions.effect', $faceless->options->caption_effect),
            overlay: Arr::get($input, 'overlay', $faceless->options->overlay),
            voiceover: $faceless->options->voiceover,
            watermark_position: Arr::get($input, 'watermark.position', $faceless->options->watermark_position),
            watermark_opacity: Arr::get($input, 'watermark.opacity', $faceless->options->watermark_opacity),
        );
    }
}
