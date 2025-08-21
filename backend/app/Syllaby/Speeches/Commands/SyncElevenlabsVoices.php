<?php

namespace App\Syllaby\Speeches\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use App\Syllaby\Speeches\Vendors\Speaker;

class SyncElevenlabsVoices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'speeches:sync-elevenlabs-voices';

    /**
     * The console command description.
     */
    protected $description = 'Fetch and saves in storage voices from Elevenlabs.';

    /**
     * The file to fetch the voices from.
     */
    protected string $file = 'voices.csv';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $file = storage_path("data/voices/{$this->file}");

        if (! file_exists($file)) {
            throw new Exception('File not found');
        }

        $file = fopen($file, 'r');

        fgetcsv($file); // Skip the header

        $voices = [];
        $order = 1;

        while (($data = fgetcsv($file)) !== false) {
            $voices[] = $this->parseCSV($data);
        }

        $voices = Collection::make($voices)
            ->sortByDesc('is_active')
            ->values()
            ->map(fn ($voice, $index) => array_merge($voice, ['order' => $order + $index]))
            ->mapWithKeys(fn ($voice) => [$voice['provider_id'] => $voice])
            ->all();

        Speaker::driver('elevenlabs')->voices($voices);

        $this->info('Voices synced successfully');
    }

    private function parseCSV(array $data): array
    {
        return [
            'name' => $data[1],
            'gender' => $data[2],
            'language' => $data[3],
            'accent' => $data[4],
            'preview_url' => $data[5],
            'provider' => $data[6],
            'provider_id' => $data[7],
            'type' => $data[8],
            'is_active' => boolval($data[9]),
            'metadata' => json_decode($data[10], true),
            'words_per_minute' => intval(round($data[11])),
        ];
    }
}
