<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Actions\SyncItemsFromSheet;

Artisan::command('items:sync-google', function (SyncItemsFromSheet $sync) {
    $count = $sync();
    $this->info("Synced {$count} rows.");
})->purpose('Sync Items from Google Sheet');

Schedule::command('items:sync-google')
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
