<?php
declare(strict_types=1);
namespace App\Observability;
use Inspector\Neuron\V4\InspectorSubscriber;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\Observability\ToolCalled;
use NeuronAI\Observability\LogListener;
use NeuronAI\Observability\ObservabilityEvent;
use Psr\Log\LoggerInterface;
final readonly class AgentMonitor
{
    public function __construct(
        private ?LoggerInterface $logger = null,
        private ?InspectorSubscriber $inspector = null,
    ) {
    }
    public function attach(Agent $agent): Agent
    {
        $agent->subscribe(ObservabilityEvent::class,
            function (ObservabilityEvent $event): void {
                echo 'Event: ' . $event->name() . PHP_EOL;
            });
        $agent->subscribe(ToolCalled::class,
            function (ToolCalled $event): void {
                echo 'Completed tool: ' . $event->tool->getName()
                    . ' ' . json_encode($event->tool->getInputs())
                    . PHP_EOL;
            });
        if ($this->logger !== null) {
            $agent->subscribe(ObservabilityEvent::class,
                new LogListener($this->logger));
        }
        if ($this->inspector !== null) {
            $agent->subscribe(ObservabilityEvent::class,
                $this->inspector);
        }
        return $agent;
    }
}
