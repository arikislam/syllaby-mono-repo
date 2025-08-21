<?php

namespace App\Syllaby\Clonables\Commands;

use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use App\Syllaby\RealClones\Avatar;
use App\Syllaby\Clonables\Clonable;
use App\Syllaby\Clonables\Vendors\Avatars\DigitalTwin;
use App\Syllaby\RealClones\Services\CreateAvatarPreview;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use App\Syllaby\Clonables\Vendors\Avatars\UserCloneData;

class CloneUserAvatar extends Command implements PromptsForMissingInput
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'clonable:user-avatar {clonable} {--provider=fastvideo} {--source=}';

    /**
     * The console command description.
     */
    protected $description = 'Triggers the process of cloning a user owned avatar.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!$clonable = Clonable::find($this->argument('clonable'))) {
            $this->error('No clone intent with the given id found.');

            return;
        }

        ['provider' => $provider, 'source' => $source] = $this->options();
        $response = DigitalTwin::driver($provider)->clone($clonable, $source);

        $avatar = $this->createAvatar($clonable, $response);
        CreateAvatarPreview::from($source, $avatar);

        $clonable->update([
            'model_id' => $avatar->id,
            'status' => $response->status,
        ]);

        $this->info('User real clone avatar model training started.');
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'clonable' => 'Avatar clone intent id:',
        ];
    }

    /**
     * Persist the cloned avatar in storage.
     */
    private function createAvatar(Clonable $clonable, UserCloneData $response): Avatar
    {
        return Avatar::create([
            'is_active' => false,
            'type' => Avatar::REAL_CLONE,
            'user_id' => $clonable->user_id,
            'provider' => $response->provider,
            'provider_id' => $response->provider_id,
            'name' => Arr::get($clonable->metadata, 'name'),
            'gender' => Arr::get($clonable->metadata, 'gender'),
        ]);
    }
}
