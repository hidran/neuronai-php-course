<?php
declare(strict_types=1);
namespace App\Observability;
use Inspector\Neuron\V4\InspectorSubscriber;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\Observability\ToolCalled;
use NeuronAI\Observability\LogListener;
use NeuronAI\Observability\ObservabilityEvent;
class AgentMonitoring
{
    public static function local(Agent $agent): void
    {
        $agent->subscribe(ObservabilityEvent::class,
            function (ObservabilityEvent $event): void {
                echo 'Event: ' . $event->name() . PHP_EOL;
            });
    }
    public static function tools(Agent $agent): void
    {
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
    }
    public static function logger(Agent $agent, string $path): void
    {
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
    }
    public static function backend(Agent $agent): void
    {
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
        // Add the listener here.
    }
}
