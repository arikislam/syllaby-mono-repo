<?php

namespace App\Syllaby\Clonables\Actions;

use App\Syllaby\Speeches\Voice;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Clonables\Vendors\Voices\Recorder;
use Illuminate\Database\Eloquent\Relations\Relation;

class DeleteClonableAction
{
    public function handle(Clonable $clonable): bool
    {
        return match ($clonable->model_type) {
            Relation::getMorphAlias(Voice::class) => $this->deleteVoice($clonable),
            Relation::getMorphAlias(Avatar::class) => $this->deleteAvatar($clonable),
            default => true
        };
    }

    private function deleteVoice(Clonable $clonable): bool
    {
        $voice = $clonable->model;

        if (! Recorder::driver($voice->provider)->remove($voice)) {
            return false;
        }

        DB::transaction(function () use ($clonable, $voice) {
            $voice->delete();

            return $clonable->delete();
        });

        return true;
    }

    public function deleteAvatar(Clonable $clonable): bool
    {
        return DB::transaction(function () use ($clonable) {
            $clonable->model->delete();

            return $clonable->delete();
        });
    }
}
