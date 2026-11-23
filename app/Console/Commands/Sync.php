<?php

namespace App\Console\Commands;

use App\Cases\Sync as Run;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('gtfs:sync {--force : Import even if the feed did not change}')]
#[Description('Download the GTFS feed and rebuild the transit network')]
class Sync extends Command
{
    public function handle(Run $run): int
    {
        ini_set('memory_limit', '1G');

        $change = $run((bool) $this->option('force'));

        if (! $change) {
            $this->info('Feed unchanged.');

            return self::SUCCESS;
        }

        $this->info("Feed {$change->version}: {$change->total} lines.");
        $this->line('Added: '.(implode(', ', $change->added) ?: '-'));
        $this->line('Removed: '.(implode(', ', $change->removed) ?: '-'));

        Log::info('gtfs:sync', [
            'version' => $change->version,
            'total' => $change->total,
            'added' => $change->added,
            'removed' => $change->removed,
        ]);

        return self::SUCCESS;
    }
}
