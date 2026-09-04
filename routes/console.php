<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Artisan::call('app:run-scheduled-backup');
})
    ->name('app-run-scheduled-backup')
    ->everyMinute()
    ->withoutOverlapping()
    ->timezone('Asia/Jakarta');

Schedule::command('attendance:auto-checkout')
    ->everyMinute()
    ->withoutOverlapping(10)
    ->timezone('Asia/Jakarta');
