<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:status', function () {
    $driver = config('neuron.provider.default')
        ?: $this->fail('Set NEURON_AI_PROVIDER first.');
    $model = config("neuron.provider.$driver.model");
    $this->info("Provider: $driver, model: $model");
});
