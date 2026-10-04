<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Importação dos XMLs a cada hora. O agendador só executa se o cron do sistema
// chamar "php artisan schedule:run" a cada minuto (ver README).
Schedule::command('import:xml all')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/import.log'));
