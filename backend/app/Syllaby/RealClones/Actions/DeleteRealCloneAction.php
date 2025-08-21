<?php

namespace App\Syllaby\RealClones\Actions;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Syllaby\RealClones\RealClone;
use Illuminate\Database\Eloquent\Model;

class DeleteRealCloneAction
{
    /**
     * Attempts to remove from storage the given real clone and its dependencies.
     *
     * @var RealClone $clone
     */
    public function handle(Model $clone): bool
    {
        try {
            attempt(fn () => $this->remove($clone));
        } catch (Exception $exception) {
            return $this->fail($clone, $exception->getMessage());
        }

        return true;
    }

    /**
     * Removes from storage the given real clone and its dependencies.
     *
     * @var RealClone $clone
     */
    private function remove(Model $clone): void
    {
        $clone->generator?->delete();
        $clone->speech?->delete();
        $clone->delete();
    }

    /**
     * Log the reason for a failed video deletion attempt.
     *
     * @var RealClone $clone
     */
    private function fail(Model $clone, string $reason): bool
    {
        Log::error('Unable to delete real clone :id due to :reason', [
            'id' => $clone->id,
            'reason' => $reason,
        ]);

        return false;
    }
}
