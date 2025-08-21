<?php

namespace App\Syllaby\Videos\Actions;

use Illuminate\Support\Arr;
use App\Syllaby\Videos\Footage;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Videos\Enums\VideoStatus;

class UpdateFootageAction
{
    /**
     * Updates the video footage in storage.
     */
    public function handle(Footage $footage, array $input): Footage
    {
        $timeline = $footage->timeline;

        $source = array_merge($timeline->content, Arr::only($input, ['width', 'height']));

        if ($timeline->rehash($source) === $timeline->hash) {
            return $footage;
        }

        return DB::transaction(function () use ($footage, $timeline, $source) {
            $footage->video->update([
                'synced_at' => null,
                'updated_at' => now(),
                'status' => VideoStatus::DRAFT,
            ]);

            $timeline->update(['content' => $source]);

            return $footage;
        });
    }
}
