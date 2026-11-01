<?php
declare(strict_types=1);
namespace App\Providers;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use NeuronAI\Laravel\AIProviderManager;
use NeuronAI\Providers\AIProviderInterface;
final class AppServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->bind(
            AIProviderInterface::class,
            fn (Application $app) => $app
                ->make(AIProviderManager::class)
                ->driver(),
        );
    }
}
