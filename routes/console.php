<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requer o cron do Laravel Scheduler activo na VPS:
// * * * * * php artisan schedule:run (ver auditoria funcional — Histórico de Alertas).
Schedule::command('alerts:generate')->daily();
