<?php

namespace App\Syllaby\RealClones\Commands;

use Illuminate\Console\Command;
use App\Syllaby\RealClones\Vendors\Presenter;

class SyncHeygenAvatars extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'real-clones:sync-heygen-avatars';

    /**
     * The console command description.
     */
    protected $description = 'Fetch and saves in storage avatars from Heygen.';

    /**
     * Allowed lis of avatars.
     */
    protected array $allowed = [
        'Angela-inblackskirt-20220820',
        'Ben-pro-insuit-20221207',
        'Daisy-insuit-20220818',
        'Jake-incasualsuit-20220721',
        'Jeff-inTshirt-20220722',
        'Joon-inblackshirt-20220821',
        'Luna-whitedress-20220530',
        'Mido-pro-flowershirt-20221208',
        'Peter-blueshirtfullbody-20220608',
        'Selina-blackabaya-20220608',
        'Thomas-purplepolo-20220609',
        'Tyler-incasualsuit-20220721',
        'Zoey-inTshirt-20220816',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Presenter::driver('heygen')->avatars($this->allowed);

        $this->info('Heygen avatars synced successfully');
    }
}
