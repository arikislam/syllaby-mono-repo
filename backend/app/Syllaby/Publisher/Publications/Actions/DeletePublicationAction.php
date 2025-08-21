<?php

namespace App\Syllaby\Publisher\Publications\Actions;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Syllaby\Publisher\Publications\Publication;

class DeletePublicationAction
{
    /**
     * Attempts to remove from storage the given publication and its dependencies.
     */
    public function handle(Publication $publication): bool
    {
        try {
            attempt(fn () => $this->remove($publication));
        } catch (Exception $exception) {
            Log::error('Unable to delete publication', ['message' => $exception->getMessage()]);

            return false;
        }

        return true;
    }

    /**
     * Removes from storage the given publication and its dependencies.
     */
    private function remove(Publication $publication): void
    {
        $publication->event()->delete();
        $publication->channels()->detach();
        $publication->metrics()->delete();
        $publication->delete();
    }
}
