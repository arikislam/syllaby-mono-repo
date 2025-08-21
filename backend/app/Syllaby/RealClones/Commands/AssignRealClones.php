<?php

namespace App\Syllaby\RealClones\Commands;

use App\Syllaby\Users\User;
use Illuminate\Support\Arr;
use Illuminate\Console\Command;
use App\Syllaby\RealClones\Avatar;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;

class AssignRealClones extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'real-clones:assign-owner';

    /**
     * The console command description.
     */
    protected $description = 'Adds real clones to the proper owner';

    /**
     * Map of owner email with real clone provider id.
     */
    protected array $realClones = [
        'custom_syllaby_eric-ORwuYSNvrs' => 'eabrego@emortgagecapital.com',
        'custom_syllaby_darren-yEpjStOFW5' => 'dkidder@emortgagecapital.com',
        'custom_syllaby_jeremy-ulaoesKuqE' => 'jscott@emortgagecapital.com',
        'custom_syllaby_adetia-QkuWptWlA1' => 'achoksi@emortgagecapital.com',
        'custom_syllaby_joe-7n3Xz7faT5' => 'joe@emortgagecapital.com',
        'custom_syllaby_joe_v2-NplyscQO6b' => 'joe@emortgagecapital.com',
        'custom_syllaby_andreab-3TTqNf_PVR' => 'andreabrognano@achievewithandrea.com',
        'v2_custom_syllaby_joe@yrnilk5lvy' => 'joe@colopy.com',
    ];

    /**
     * Map of owner email with real clone provider id.
     */
    protected array $realClonesLite = [
        'avt_Gjyvx2T9d_fKZC3rehBnb' => [
            'email' => 'muluayele@gmail.com',
            'gender' => 'male',
        ],
        'avt_JDLgVrlhlBLmQeVkF6ozP' => [
            'email' => 'cw@sithmarketing.com',
            'gender' => 'male',
        ],
        'avt_a1v5b-zwn0nEP7rQZWBY4' => [
            'email' => 'deren.flesher@gmail.com',
            'gender' => 'male',
        ],
        'avt_zIYS2jSXjElV4NYm5pt5d' => [
            'email' => 'austin@syllaby.io',
            'gender' => 'male',
        ],
        'avt_6Nn-NxP8SQOm0yUH5Ia0v' => [
            'email' => 'hijessbrown@gmail.com',
            'gender' => 'female',
        ],
        'avt_niGPuR_o2d0Pa5mx7YF7s' => [
            'email' => 'jed.deaver@gmail.com',
            'gender' => 'male',
        ],
        'avt_tk1oUUe9YsOKEATVfqNxZ' => [
            'email' => 'mikeduffybsc@gmail.com',
            'gender' => 'male',
        ],
        'avt_s5Nf2DYkyfqFkKZSruCSh' => [
            'email' => 'md@artmos4.de',
            'gender' => 'male',
        ],
        'avt_0w12YJxkPy6tPyvJjHYQg' => [
            'email' => 'paulomahony1@gmail.com',
            'gender' => 'male',
        ],
        'avt_E6In0Xsk_nBACv-jDT3VE' => [
            'email' => 'paulomahony1@gmail.com',
            'gender' => 'male',
        ],
        'avt_LUKP2-ElecnaHD9na-45v' => [
            'email' => 'austin@syllaby.io',
            'gender' => 'male',
        ],
        'avt_zwccpfVrBqtnYFe7rCkDc' => [
            'email' => 'austin@syllaby.io',
            'gender' => 'male',
        ],
        'avt_lj_D1n-J1UB63se0Vshcq' => [
            'email' => 'drg92614@gmail.com',
            'gender' => 'male',
        ],
        'avt_k0ZPFvSfVu4GrALeAb3yB' => [
            'email' => 'ebasu001@gmail.com',
            'gender' => 'male',
        ],
        'avt_5m3TfUcTBkZOoZjkFqcze' => [
            'email' => 'jessica@badass-blueprint.com',
            'gender' => 'female',
        ],
        'avt_Jf0G379NEZekF_62ZZrIz' => [
            'email' => 'joe.newkirk@gmail.com',
            'gender' => 'male',
        ],
        'avt_VIek7FfErrHiASoeXI2W1' => [
            'email' => 'ange@theopsbuilder.com',
            'gender' => 'female',
        ],
        'avt_cXYfx6jxD3k12C4yX4mMk' => [
            'email' => 'pralayrb@gmail.com',
            'gender' => 'male',
        ],
        'avt_aeglyiU9sXf0ZA0AzTiA-' => [
            'email' => 'rakinsetesocials@gmail.com',
            'gender' => 'male',
        ],
        'avt_hliR0zYJ17dpXj9lrkeme' => [
            'email' => 'hello@profitsurgegroup.com',
            'gender' => 'male',
        ],
        'avt_EJgLim2HLwENnK-WudRgT' => [
            'email' => 'dominic4401@gmail.com',
            'gender' => 'male',
        ],
    ];

    /**
     * Execute the console command.
     *
     * @throws RequestException
     */
    public function handle(): void
    {
        $this->syncRealClones();
        $this->syncRealClonesLite();
    }

    /**
     * Sync real clones from D-ID.
     */
    private function syncRealClones(): void
    {
        foreach ($this->realClones as $providerId => $email) {
            $response = $this->http()->get("/clips/presenters/{$providerId}");

            if ($response->failed()) {
                $response->throw(fn () => Log::error('Error while fetching D-ID real clones.'));
            }

            $this->create($email, [
                'provider_id' => $providerId,
                'type' => Avatar::REAL_CLONE,
                'gender' => $response->json('presenters.0.gender'),
                'owner_id' => $response->json('presenters.0.owner_id'),
                'model_url' => $response->json('presenters.0.model_url'),
                'driver_id' => $response->json('presenters.0.driver_id'),
                'thumbnail_url' => $response->json('presenters.0.thumbnail_url'),
            ]);

            $this->info("Creating for: {$email}");
        }

        $this->info('D-ID real clones assigned synced successfully');
    }

    /**
     * Sync real clones from D-ID.
     */
    private function syncRealClonesLite(): void
    {
        foreach ($this->realClonesLite as $providerId => $avatar) {
            $response = $this->http()->get("/scenes/avatars/{$providerId}");

            if ($response->failed()) {
                $response->throw(fn () => Log::error('Error while fetching D-ID express avatars.'));
            }

            $this->create($avatar['email'], [
                'provider_id' => $providerId,
                'gender' => $avatar['gender'],
                'type' => Avatar::REAL_CLONE_LITE,
                'thumbnail_url' => $response->json('thumbnail_url'),
            ]);

            $this->info("Creating for: {$avatar['email']}");
        }

        $this->info('D-ID express avatars assigned synced successfully');
    }

    /**
     * Create a real clone.
     */
    private function create(string $email, array $data): void
    {
        if (! $user = User::where('email', $email)->first()) {
            $this->warn("No user found with email: {$email}");

            return;
        }

        Avatar::updateOrCreate(
            [
                'provider' => 'd-id',
                'user_id' => $user->id,
                'provider_id' => Arr::get($data, 'provider_id'),
            ],
            [
                'is_active' => true,
                'type' => Arr::get($data, 'type'),
                'gender' => Arr::get($data, 'gender'),
                'preview_url' => Arr::get($data, 'thumbnail_url'),
                'name' => $user->name,
                'metadata' => Arr::only($data, ['owner_id', 'model_url', 'driver_id']),
            ]
        );
    }

    /**
     * Get the HTTP client.
     */
    private function http(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl(config('services.d-id.url'))
            ->withHeaders(['Authorization' => 'Basic '.config('services.d-id.key')]);
    }
}
