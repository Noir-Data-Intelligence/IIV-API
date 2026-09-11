<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Requer o cron do Laravel Scheduler activo na VPS:
// * * * * * php artisan schedule:run (ver auditoria funcional — Histórico de Alertas).
// Cadência horária (não diária): SLAs de 24h (ex. Bacteriologia, Biologia
// Molecular) não podem esperar por um alerta que só corre uma vez por dia.
Schedule::command('alerts:generate')->hourly();
