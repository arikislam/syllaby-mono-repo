<?php

namespace App\Syllaby\Ideas\Commands;

use DB;
use Str;
use Exception;
use App\Syllaby\Users\User;
use Illuminate\Console\Command;
use App\Syllaby\Ideas\Enums\Networks;
use App\Syllaby\Users\Enums\UserType;
use App\Syllaby\Ideas\Services\KeywordTool;

class FetchPublicIdeasCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'syllaby:fetch-public-ideas {--network=google : The network to fetch ideas from} {--keywords=* : List of keywords to fetch ideas for}';

    /**
     * The console command description.
     */
    protected $description = 'Fetch public ideas from KeywordTool as system user';

    /**
     * Execute the console command.
     */
    public function handle(KeywordTool $idea): int
    {
        try {
            $system = User::where('user_type', UserType::ADMIN->value)->firstOrFail();

            $network = Networks::from($this->option('network'));

            $keywords = $this->option('keywords') ?: $this->getDefaultKeywords();

            $this->output->progressStart(count($keywords));

            foreach ($keywords as $keyword) {
                DB::transaction(fn () => $this->processKeyword($idea, $system, $network, $keyword));
                $this->output->progressAdvance();
            }

            $this->output->progressFinish();

            return self::SUCCESS;
        } catch (Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Process a single keyword.
     */
    private function processKeyword(KeywordTool $idea, User $system, Networks $network, string $keyword): void
    {
        try {
            $result = $idea->search($keyword, ['network' => $network->value], $system);
            $result->ideas()->update(['public' => true]);
            $result->users()->syncWithPivotValues($system, ['updated_at' => now(), 'audience' => Str::slug($keyword)], false);

            $this->output->info(sprintf('Found %d ideas for "%s"', $result->ideas()->count(), $keyword));
        } catch (Exception $e) {
            $this->output->warning(sprintf('Failed to fetch ideas for "%s": %s', $keyword, $e->getMessage()));
        }
    }

    /**
     * Get default keywords if none provided.
     */
    private function getDefaultKeywords(): array
    {
        return [
            'Legal',
            'Energy',
            'Retail',
            'Defense',
            'Finance',
            'Aerospace',
            'Education',
            'Consulting',
            'Government',
            'Healthcare',
            'Non-Profit',
            'Technology',
            'Agriculture',
            'Hospitality',
            'Real Estate',
            'Construction',
            'Manufacturing',
            'Transportation',
            'Pharmaceuticals',
            'Media and Entertainment',
        ];
    }
}
