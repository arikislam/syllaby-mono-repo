<?php

namespace App\Console;

use App\Syllaby\Scraper\ScraperLog;
use Illuminate\Console\Scheduling\Schedule;
use App\Syllaby\Publisher\Metrics\PublicationMetricValue;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected array $modules = [
        'Ideas',
        'Assets',
        'Videos',
        'Speeches',
        'Clonables',
        'RealClones',
        'Schedulers',
        'Subscriptions',
        'Publisher/Metrics',
        'Publisher/Channels',
        'Publisher/Publications',
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('cache:prune-stale-tags')->hourly();
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('sanctum:prune-expired --hours=72')->daily();
        $schedule->command('queue:prune-batches --hours=24 --cancelled=48 --unfinished=48')->daily();
        $schedule->command('queue:prune-failed --hours=72')->daily();

        $schedule->command('syllaby:prune-media-bundles')->hourly();
        $schedule->command('syllaby:release-credits')->dailyAt('02:15');
        $schedule->command('syllaby:release-jvzoo-credits')->dailyAt('02:20');
        $schedule->command('syllaby:prune-scheduler-reminders')->daily();
        $schedule->command('syllaby:scheduler-reminders')->withoutOverlapping()->everyMinute();
        $schedule->command('syllaby:send-subscription-alerts')->environments('production')->daily();
        $schedule->command('syllaby:fetch-public-ideas')->quarterly()->withoutOverlapping();
        $schedule->command('syllaby:complete-schedulers')->everyFourHours();

        $schedule->command('youtube:categories')->monthly();
        $schedule->command('tiktok:refresh-access-tokens')->daily();
        $schedule->command('videos:timeout-monitor')->daily();
        $schedule->command('threads:refresh-access-tokens')->weekly();
        $schedule->command('metrics:fetch-publication-metrics')->withoutOverlapping()->dailyAt('00:00');
        $schedule->command('metrics:compute-aggregate')->withoutOverlapping()->dailyAt('01:00');
        $schedule->command('publish:scheduled')->everyMinute();

        $schedule->command('model:prune', [
            '--model' => [PublicationMetricValue::class, ScraperLog::class],
        ])->environments('production')->weekly();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        foreach ($this->modules as $module) {
            $this->load(__DIR__."/../Syllaby/{$module}/Commands");
        }

        require base_path('routes/console.php');
    }
}
