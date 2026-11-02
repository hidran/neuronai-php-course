<?php
declare(strict_types=1);
namespace App\Providers;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use NeuronAI\Laravel\AIProviderManager;
use NeuronAI\Laravel\EmbeddingProviderManager;
use NeuronAI\Laravel\VectorStoreManager;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
final class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(VectorStoreManager::class);
        $this->app->bind(
            AIProviderInterface::class,
            fn (Application $app) => $app
                ->make(AIProviderManager::class)
                ->driver(),
        );
        $this->app->bind(
            EmbeddingsProviderInterface::class,
            fn (Application $app) => $app
                ->make(EmbeddingProviderManager::class)
                ->driver(),
        );
        $this->app->bind(
            VectorStoreInterface::class,
            fn (Application $app) => $app
                ->make(VectorStoreManager::class)
                ->driver(),
        );
    }
}
