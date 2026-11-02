<?php
// Offline check of the ecosystem lesson (FakeAIProvider, no model call, no key, no network).
// Usage: php verify-ecosystem.php <lab>. Checks what the narration states about the course lab, the installed core
// package and this folder's prepared material; the Maestro sandbox next to the lab is checked when it exists.
declare(strict_types=1);

$lab = $argv[1] ?? throw new RuntimeException('usage: verify-ecosystem.php <lab>');
require $lab . '/vendor/autoload.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\Observability\ToolCalled;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\ObservabilityEvent;
use NeuronAI\Testing\FakeAIProvider;

function check(bool $ok, string $what): void
{
    if (!$ok) {
        throw new RuntimeException('FAILED: ' . $what);
    }
}

// Inspector: a separate Composer package with a V4 subscriber we attach ourselves; nothing is attached by default.
check(class_exists(Inspector\Neuron\V4\InspectorSubscriber::class), 'inspector-apm/inspector-php ships the V4 subscriber');
check(is_file($lab . '/vendor/neuron-core/neuron-ai/composer.json'), 'the core package is installed');
$core = json_decode((string) file_get_contents($lab . '/vendor/neuron-core/neuron-ai/composer.json'), true);
check(!isset($core['require']['inspector-apm/inspector-php']), 'the core package does not require Inspector');
$seen = [];
$agent = Agent::make()->setAiProvider(new FakeAIProvider(new AssistantMessage('Done.')))->for('ecosystem-1');
$agent->chat(new UserMessage('Hello'));
$watched = Agent::make()->setAiProvider(new FakeAIProvider(new AssistantMessage('Done.')))->for('ecosystem-2')
    ->subscribe(ObservabilityEvent::class, function (ObservabilityEvent $event) use (&$seen): void {
        $seen[] = $event->name();
    });
$watched->chat(new UserMessage('Hello'));
check(in_array('workflow-end', $seen, true), 'a subscribed agent emits workflow-end');
check(class_exists(ToolCalled::class), 'ToolCalled is the tool event the monitor prints');

// The traced fixture of the observability lessons, as the lab holds it.
foreach (['examples/49-observability.php', 'src/Observability/AgentMonitor.php', 'src/Agents/ObservedWeatherAgent.php',
    'src/Tools/FixtureWeatherTool.php', 'storage/inspector-hook.php'] as $path) {
    check(is_file($lab . '/' . $path), "lab file {$path}");
}

// The Symfony integration skill shipped by the core package, and the sections the narration names.
$skill = (string) file_get_contents($lab . '/vendor/neuron-core/neuron-ai/skills/neuron-symfony-integration/SKILL.md');
foreach (['### 5. Container wiring', 'NeuronAI\Chat\History\SQLMessageStore', 'NeuronAI\Workflow\Persistence\DatabasePersistence',
    '## Thread Identity and Authorization', '## A JSON Chat Endpoint', '## Background Runs with Messenger'] as $needle) {
    check(str_contains($skill, $needle), "Symfony skill mentions {$needle}");
}
check(!preg_match('#neuron-core/(neuron-)?symfony#', $skill), 'the skill names no separate Symfony SDK package');

// This folder's prepared material.
$notes = (string) file_get_contents(__DIR__ . '/neuronai-studio.txt');
check(str_contains($notes, '"neuron-core/neuron-ai": "^3.17"') && str_contains($notes, 'c35b36a'), 'Studio notes');
$fixture = json_decode((string) file_get_contents(__DIR__ . '/fixture/composer.json'), true);
check(($fixture['require']['php'] ?? '') === '>=8.5', 'the Maestro fixture declares php >=8.5');
$sandbox = json_decode((string) file_get_contents(__DIR__ . '/maestro-sandbox.composer.json'), true);
check(($sandbox['require']['neuron-core/maestro'] ?? '') === '1.3.11', 'the sandbox pins Maestro 1.3.11');

// The Maestro sandbox, when prepare.sh built it next to the lab: its own vendor with the third major.
$installed = dirname($lab) . '/maestro-sandbox/vendor/neuron-core/maestro/composer.json';
if (is_file($installed)) {
    $maestro = json_decode((string) file_get_contents($installed), true);
    check(($maestro['require']['neuron-core/neuron-ai'] ?? '') === '^3.0', 'Maestro requires neuron-ai ^3.0');
    $listener = (string) file_get_contents(dirname($installed) . '/src/Listeners/CliOutputListener.php');
    check(str_contains($listener, "'Allow for session'") && str_contains($listener, "'Reject'"), 'Maestro asks before each tool');
}

echo 'ECOSYSTEM OK' . PHP_EOL;
