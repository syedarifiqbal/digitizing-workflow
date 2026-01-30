<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('demo:reset')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->timezone('UTC');

Schedule::command('invoices:mark-overdue')
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->timezone('UTC');
