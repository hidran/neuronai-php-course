<?php
declare(strict_types=1);
namespace App\Providers;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use NeuronAI\Laravel\AIProviderManager;
use NeuronAI\Providers\AIProviderInterface;
use Inspector\Neuron\V4\InspectorSubscriber;
use NeuronAI\Agent\Agent;
use NeuronAI\Observability\ObservabilityEvent;
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
        $this->app->afterResolving(
            Agent::class,
            fn (Agent $agent) => $agent->subscribe(
                ObservabilityEvent::class,
                new InspectorSubscriber(inspector()),
            ),
        );
    }
}
