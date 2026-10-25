<?php
declare(strict_types=1);
namespace App\Observability;
use NeuronAI\Agent\Observability\InferenceStart;
use NeuronAI\Agent\Observability\InferenceStop;
use NeuronAI\Agent\Observability\ToolCalled;
use NeuronAI\Agent\Observability\ToolCalling;
use NeuronAI\Observability\ObservabilityEvent;
use NeuronAI\Tools\ToolOutput;
final class TraceRecorder
{
    private readonly int $started;
    /** @var array<string, int> */
    private array $open = [];
    /** @var list<array<string, mixed>> */
    private array $inferences = [];
    /** @var list<array<string, mixed>> */
    private array $tools = [];
    public function __construct(
        private readonly Prices $prices,
        private readonly string $model,
    ) {
        $this->started = hrtime(true);
    }
    public function __invoke(ObservabilityEvent $event): void
    {
        $now = hrtime(true);
        $scope = 'inference:' . ($event->branchId ?? 'main');
        if ($event instanceof InferenceStart) {
            $this->open[$scope] = $now;
        } elseif ($event instanceof InferenceStop) {
            $usage = $event->response->message()->getUsage();
            $this->inferences[] = [
                'duration_ms' => $this->elapsed($scope, $now),
                'usage' => $usage?->jsonSerialize(),
                'cost_usd' => $this->prices->costUsd($this->model,
                    $usage),
            ];
        } elseif ($event instanceof ToolCalling) {
            $this->open['tool:' . $event->tool->getCallId()] = $now;
        } elseif ($event instanceof ToolCalled) {
            $call = $event->tool;
            $result = $call->getResult();
            $this->tools[] = [
                'call_id' => $call->getCallId(),
                'name' => $call->getName(),
                'inputs' => $call->getInputs(),
                'result' => (string) $result,
                'is_error' => $result instanceof ToolOutput
                    && $result->isError(),
                'duration_ms' => $this->elapsed(
                    'tool:' . $call->getCallId(), $now),
            ];
        }
    }
    private function elapsed(string $key, int $now): ?float
    {
        $start = $this->open[$key] ?? null;
        unset($this->open[$key]);
        return $start === null ? null : ($now - $start) / 1e6;
    }
    /** @return list<array<string, mixed>> */
    public function inferences(): array
    {
        return $this->inferences;
    }
    /** @return list<array<string, mixed>> */
    public function tools(): array
    {
        return $this->tools;
    }
    public function wallMilliseconds(): float
    {
        return (hrtime(true) - $this->started) / 1e6;
    }
}
