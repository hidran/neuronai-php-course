<?php
declare(strict_types=1);
// Offline check (no key, no network): MonitoringSettings, and the trace the InspectorSubscriber builds for the hello
// world agent, captured by an in-memory transport instead of being sent. Needs inspector-apm/inspector-php in the lab.
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
if (!class_exists(Inspector\Neuron\V4\InspectorSubscriber::class)) {
    throw new RuntimeException('run composer require inspector-apm/inspector-php in the lab first');
}
require __DIR__ . '/MonitoringSettings.php';
require_once __DIR__ . '/../../01-06-hello-neuronai/files/HelloAgent.php';
use App\Config\MonitoringSettings;
use App\HelloAgent;
use Inspector\Configuration;
use Inspector\Inspector;
use Inspector\Models\Model;
use Inspector\Models\Segment;
use Inspector\Models\Transaction;
use Inspector\Neuron\V4\InspectorSubscriber;
use Inspector\Transports\TransportInterface;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\ObservabilityEvent;
use NeuronAI\Testing\FakeAIProvider;
final class CapturingTransport implements TransportInterface
{
    /** @var list<Model> */
    public array $queue = [];
    /** @var list<Model> */
    public array $sent = [];
    public function addEntry(Model $model): TransportInterface
    {
        $this->queue[] = $model;
        return $this;
    }
    public function resetQueue(): TransportInterface
    {
        $this->queue = [];
        return $this;
    }
    public function flush(): TransportInterface
    {
        $this->sent = [...$this->sent, ...$this->queue];
        return $this->resetQueue();
    }
}
// 1. MonitoringSettings: the key under INSPECTOR_INGESTION_KEY, a clear error without it.
if (MonitoringSettings::fromEnv(['INSPECTOR_INGESTION_KEY' => 'k1'])->ingestionKey !== 'k1') {
    throw new RuntimeException('ingestion key not read');
}
foreach ([[], ['INSPECTOR_INGESTION_KEY' => '']] as $env) {
    try {
        MonitoringSettings::fromEnv($env);
        throw new LogicException('missing key accepted');
    } catch (RuntimeException $e) {
        if ($e->getMessage() !== 'INSPECTOR_INGESTION_KEY is not set') {
            throw new RuntimeException('unexpected message: ' . $e->getMessage());
        }
    }
}
echo 'missing key: INSPECTOR_INGESTION_KEY is not set' . PHP_EOL;
// 2. The same wiring as the lesson's script, with a fake provider and the capturing transport.
$transport = new CapturingTransport();
$inspector = new Inspector(new Configuration('offline-check-key'));
$inspector->setTransport($transport);
$fake = new FakeAIProvider(new AssistantMessage('An agent is a loop.'));
$state = HelloAgent::make($fake)->setThreadId('hello')
    ->subscribe(ObservabilityEvent::class, new InspectorSubscriber($inspector))
    ->chat(new UserMessage('What is an AI agent, in plain words?'));
if ($state->getMessage()?->getContent() !== 'An agent is a loop.') {
    throw new RuntimeException('unexpected answer');
}
if ($transport->queue !== []) {
    throw new RuntimeException('the subscriber did not flush at the end of the run');
}
$transactions = array_values(array_filter($transport->sent, fn (Model $m): bool => $m instanceof Transaction));
$segments = array_values(array_filter($transport->sent, fn (Model $m): bool => $m instanceof Segment));
if (count($transactions) !== 1) {
    throw new RuntimeException('expected one transaction, got ' . count($transactions));
}
$transaction = $transactions[0];
if ($transaction->name !== HelloAgent::class || $transaction->type !== 'agent') {
    throw new RuntimeException("unexpected transaction {$transaction->name} ({$transaction->type})");
}
echo "transaction: {$transaction->name}, type {$transaction->type}, result {$transaction->result}" . PHP_EOL;
$labels = array_map(fn (Segment $s): string => $s->type . ' ' . $s->label, $segments);
foreach ($labels as $label) {
    echo "segment: {$label}" . PHP_EOL;
}
$inferences = array_filter($labels, fn (string $l): bool => str_starts_with($l, 'agent.inference '));
if (count($inferences) !== 1) {
    throw new RuntimeException('expected exactly one model call (inference segment)');
}
if (array_find($labels, fn (string $l): bool => $l === 'agent.node ChatNode') === null) {
    throw new RuntimeException('no ChatNode segment');
}
if ($fake->getCallCount() !== 1) {
    throw new RuntimeException('expected one provider call');
}
echo 'INSPECTOR OK' . PHP_EOL;
