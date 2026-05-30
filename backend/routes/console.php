<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run every day at 8 AM to mark overdue invoices and notify owners
Schedule::command('invoices:mark-overdue')->dailyAt('08:00');
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('subscriptions:renew')->hourly();
Schedule::command('subscriptions:expire')->hourly();
Schedule::command('ops:backup')->dailyAt('02:00');
Schedule::command('ops:reconcile-payments')->hourly();
Schedule::command('ops:health-scan')->hourly();
