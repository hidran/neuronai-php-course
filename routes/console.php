<?php

use App\Neuron\Agents\SupportAgent;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Workflow\Persistence\PersistenceInterface;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('ai:status', function () {
    $driver = config('neuron.provider.default')
        ?: $this->fail('Set NEURON_AI_PROVIDER first.');
    $model = config("neuron.provider.$driver.model");
    $this->info("Provider: $driver, model: $model");
});

Artisan::command('agent:lifetimes', function (): void {
    foreach ([
        MessageStoreInterface::class,
        PersistenceInterface::class,
        SupportAgent::class,
    ] as $id) {
        $same = app($id) === app($id) ? 'shared' : 'fresh';
        $this->line(class_basename(app($id)).": {$same}");
    }
});
