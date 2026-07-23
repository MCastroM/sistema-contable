<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('indicadores:actualizar')
    ->days([1, 2, 3, 4, 5, 6])
    ->dailyAt('08:30')
    ->timezone('America/Santiago');