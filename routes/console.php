<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('agent:lifetimes', function (): void {
    foreach ([
        NeuronAI\Chat\History\MessageStoreInterface::class,
        NeuronAI\Workflow\Persistence\PersistenceInterface::class,
        App\Neuron\Agents\SupportAgent::class,
    ] as $id) {
        $same = app($id) === app($id) ? 'shared' : 'fresh';
        $this->line(class_basename(app($id)) . ": {$same}");
    }
});
