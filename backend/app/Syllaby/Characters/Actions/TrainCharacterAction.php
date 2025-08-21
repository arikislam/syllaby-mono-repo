<?php

namespace App\Syllaby\Characters\Actions;

use DB;
use App\Syllaby\Assets\Media;
use App\Syllaby\Characters\Character;
use App\Syllaby\Credits\Enums\CreditEventEnum;
use App\Syllaby\Credits\Services\CreditService;
use App\Syllaby\Characters\Enums\CharacterStatus;
use App\Syllaby\Characters\Jobs\GeneratePosesJob;
use App\Syllaby\Assets\Actions\TransloadMediaAction;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class TrainCharacterAction
{
    public function __construct(protected TransloadMediaAction $transload, protected CreditService $credit) {}

    public function handle(Character $character, array $input): Character
    {
        $preview = Media::where('collection_name', 'sandbox')
            ->where('id', $input['preview_id'])
            ->where('user_id', $character->user_id)
            ->first();

        // Since user can try previews in multiple genres, hence we can only reliably set the genre
        // when the preview is selected for training.
        $character->update(['genre_id' => $preview->getCustomProperty('genre_id')]);

        $this->transload->handle($character, $preview->getFullUrl(), 'preview', [
            'reference_media_id' => $preview->getCustomProperty('reference_media_id'),
            'genre_id' => $preview->getCustomProperty('genre_id'),
        ]);

        $this->markPreviewAsUsed($character, $preview);

        $this->credit->setUser($character->user)->decrement(CreditEventEnum::CUSTOM_CHARACTER_PURCHASED, $character);

        dispatch(new GeneratePosesJob($character));

        return tap($character)->update(['status' => CharacterStatus::POSE_GENERATING]);
    }

    private function markPreviewAsUsed(Character $character, Media|SpatieMedia $preview): void
    {
        Media::where('collection_name', 'sandbox')
            ->where('model_id', $character->id)
            ->where('model_type', $character->getMorphClass())
            ->update(['custom_properties' => DB::raw(<<<'SQL'
                CASE 
                    WHEN JSON_TYPE(custom_properties) = 'ARRAY' OR custom_properties IS NULL THEN JSON_OBJECT('used', false)
                    ELSE JSON_MERGE_PATCH(custom_properties, JSON_OBJECT('used', false))
                END
SQL)]);

        $preview->setCustomProperty('used', true);
        $preview->save();
    }
}
