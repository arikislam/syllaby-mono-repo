<?php

namespace App\Syllaby\RealClones\Commands;

use Illuminate\Console\Command;
use App\Syllaby\RealClones\Vendors\Presenter;

class SyncDiDAvatars extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'real-clones:sync-did-avatars';

    /**
     * The console command description.
     */
    protected $description = 'Fetch and saves in storage avatars from D-ID.';

    /**
     * Allowed lis of avatars.
     */
    protected array $allowed = [
        'amy-Aq6OmGZnMt' => 'Amy',
        'jack-MVemepZhiL' => 'Jack',
        'lana-uXbrIxQFjr' => 'Lana',
        'lily-akobXDF34M' => 'Lily',
        'rian-lZC6MmWfC1' => 'Rian',
        'owen-tI5q5OPEbX' => 'Owen',
        'mary-d3srfuXaAk' => 'Mary',
        'alex-tv2VbI8lXI' => 'Alex',
        'ella-gnVGWQ_kNS' => 'Ella',
        'diana-Ir0x9WlpT4' => 'Diana',
        'frank-gvlo7vAP2C' => 'Frank',
        'fiona-Fi5YDeh1YS' => 'Fiona',
        'dylan-E_sy058oXA' => 'Dylan',
        'kayla-zxpG_93n5W' => 'Kayla',
        'fiona-KoeYgsGmIj' => 'Fiona',
        'flora-4BdcfiQNJP' => 'Flora',
        'anita-H_MdjUsV3A' => 'Anita',
        'jaimie-HoDsDwCz3X' => 'Jaimie',
        'joseph-ivRs8CUnJ8' => 'Joseph',
        'alyssa-Kpjhh2J_rm' => 'Alyssa',
        'sophia-utD_M2P2Lk' => 'Sophia',
        'darren-RYscOXmp8t' => 'Darren',
        'william-FPvBkeR0kv' => 'William',
        'custom_syllaby_rae-OhYROlIzI7' => 'Rae',
        'custom_syllaby_zack-cVtGDYIgMK' => 'Zack',
        'custom_syllaby_keith-KHusmCwS23' => 'Keith',
        'custom_syllaby_martin-LkYC0VvpBK' => 'Martin',
        'custom_syllaby_lauren-yCr7GDaEEA' => 'Lauren',
        'custom_syllaby_avatar1-pO6LjI9Azm' => 'Steve',
        'custom_syllaby_andrea-fmVQRepx0E' => 'Andreea',
        'custom_syllaby_kledjona-soOuyhxU99' => 'Kledjona',
        'custom_syllaby_kelly_v2-pbRp9mTVAo' => 'Kelly',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Presenter::driver('did')->avatars($this->allowed);

        $this->info('D-ID avatars synced successfully');
    }
}
