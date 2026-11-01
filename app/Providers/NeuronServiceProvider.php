<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\DB;
use NeuronAI\Chat\History\EloquentMessageStore;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Workflow\Persistence\DatabasePersistence;
use NeuronAI\Workflow\Persistence\PersistenceInterface;

class NeuronServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(MessageStoreInterface::class, fn () =>
            new EloquentMessageStore(ChatMessage::class));
        $this->app->bind(PersistenceInterface::class, fn () =>
            new DatabasePersistence(DB::getPdo()));
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
