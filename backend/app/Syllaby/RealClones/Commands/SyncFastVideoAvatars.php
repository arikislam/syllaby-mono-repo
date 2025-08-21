<?php

namespace App\Syllaby\RealClones\Commands;

use Illuminate\Console\Command;
use App\Syllaby\RealClones\Vendors\Presenter;

class SyncFastVideoAvatars extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'real-clones:sync-fastvideo-avatars';

    /**
     * The console command description.
     */
    protected $description = 'Fetch and saves in storage avatars from FastVideo.';

    /**
     * Allowed lis of avatars.
     */
    protected array $allowed = [
        'model_id' => ['name' => '', 'gender' => ''],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Presenter::driver('fastvideo')->avatars($this->allowed);

        $this->info('FastVideo avatars synced successfully');
    }
}
