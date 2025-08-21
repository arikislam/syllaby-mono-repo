<?php

namespace App\Http\Controllers\Webhooks;

use Illuminate\Http\Request;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\DB;
use App\Syllaby\Clonables\Clonable;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Syllaby\Clonables\Enums\CloneStatus;
use Symfony\Component\HttpFoundation\Response;
use App\Syllaby\Clonables\Notifications\AvatarCloneGenerated;

class FastVideoCloneWebhookController extends Controller
{
    /**
     * Handles the model clone avatar training result.
     */
    public function handle(Request $request): Response
    {
        Log::debug('clone request', $request->all());

        if (!$clonable = $this->fetchCloneIntent($request)) {
            Log::error('No clonable avatar intent found for {id}', [
                'id' => $request->query('clonable_id'),
            ]);

            return $this->success();
        }

        if ($request->integer('status') !== 200) {
            Log::error('Failed to clone {clonable} with FastVideo', [
                'clonable' => $request->query('clonable_id'),
            ]);

            return tap($this->success(), function () use ($clonable) {
                $clonable->update(['status' => CloneStatus::FAILED]);
            });
        }

        DB::transaction(function () use ($clonable) {
            $clonable->model()->update(['is_active' => true]);
            $clonable->update(['status' => CloneStatus::COMPLETED]);
        });

        $clonable->user->notify(new AvatarCloneGenerated);

        return $this->success();
    }

    /**
     * Fetch the avatar clone intent.
     */
    private function fetchCloneIntent(Request $request): ?Clonable
    {
        return Clonable::query()
            ->where('status', CloneStatus::REVIEWING)
            ->where('id', $request->query('clonable_id'))
            ->where('model_type', (new Avatar)->getMorphClass())
            ->first();
    }

    /**
     * Handle successful calls on the controller.
     */
    private function success(): Response
    {
        return new Response('Webhook Handled', 200);
    }
}
