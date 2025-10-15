<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Actions\SyncItemsFromSheet;

class SyncItemsFromGoogle extends Command
{
    protected $signature = 'items:sync-google';
    protected $description = 'Sync Items table from Google Sheet CSV';

    public function handle(SyncItemsFromSheet $sync)
    {
        try {
            $count = $sync();
            $this->info("Synced {$count} rows.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            \Log::error('[ItemsSync] '.$e->getMessage(), ['trace'=>$e->getTraceAsString()]);
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
