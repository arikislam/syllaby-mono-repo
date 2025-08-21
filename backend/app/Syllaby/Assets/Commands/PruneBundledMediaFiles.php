<?php

namespace App\Syllaby\Assets\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneBundledMediaFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'syllaby:prune-media-bundles';

    /**
     * The console command description.
     */
    protected $description = 'Prune media bundles that are older than 1 hour';

    /**
     * The maximum age of a media bundle in seconds.
     */
    const int TTL = 3600;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $folders = Storage::disk('spaces')->directories('tmp/zips');

        foreach ($folders as $folder) {
            $timestamp = (int) basename($folder);

            if ((now()->timestamp - $timestamp) > self::TTL) {
                Storage::disk('spaces')->deleteDirectory($folder);
            }
        }
    }
}
