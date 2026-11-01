<?php
declare(strict_types=1);
// Offline check of the lesson (FakeAIProvider: no model call, no network).
// Usage: php verify-copy-not-mutate.php <lab>
// <lab> is the Laravel lab of the section (neuron-laravel 2.x, NeuronAI 4.1).
// The lesson's diagnostic runs from a temporary folder next to a link to the
// lab's vendor/, so the check follows this folder's copies.php and leaves the
// lab untouched.
use App\Neuron\Agents\AssistantAgent;
use App\Neuron\Tools\OfficeHours;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Testing\FakeAIProvider;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('lab path');
// 1. What the narration reads in the installed core.
$core = $lab . '/vendor/neuron-core/neuron-ai/src';
$workflow = file($core . '/Workflow/Workflow.php', FILE_IGNORE_NEW_LINES);
if (!str_contains($workflow[126], 'public function for(string $workflowId): static')
    || !str_contains($workflow[128], '$copy = clone $this;')
    || !str_contains($workflow[129], '$copy->workflowId = null;')
    || !str_contains($workflow[131], 'return $copy->setWorkflowId($workflowId);')
    || !str_contains($workflow[121], 'shares the collaborators the definition holds')) {
    throw new RuntimeException('Workflow::for() moved or changed: re-check scenes 3b and 5');
}
$agentSource = file($core . '/Agent/Agent.php', FILE_IGNORE_NEW_LINES);
if (!str_contains($agentSource[377], 'return $this->setWorkflowId($threadId);')
    || !str_contains($agentSource[136], 'return $this->messageStore ??= $this->messageStore();')
    || !str_contains($agentSource[209], 'threadId: $this->requireWorkflowId(),')) {
    throw new RuntimeException('Agent.php moved or changed: re-check scenes 3b, 4 and 5');
}
$handleTools = file($core . '/Agent/HandleTools.php', FILE_IGNORE_NEW_LINES);
if (!str_contains($handleTools[100], 'public function addTool(')
    || !str_contains($handleTools[106], '$this->tools[] = $tool;')) {
    throw new RuntimeException('HandleTools::addTool() moved or changed: re-check scene 4');
}
// 2. The lesson's diagnostic, run as the lesson runs it.
$tmp = sys_get_temp_dir() . '/copy-not-mutate-' . bin2hex(random_bytes(4));
mkdir($tmp . '/diagnostics', 0700, true);
symlink($lab . '/vendor', $tmp . '/vendor');
copy(__DIR__ . '/copies.php', $tmp . '/diagnostics/copies.php');
register_shutdown_function(static function () use ($tmp): void {
    unlink($tmp . '/diagnostics/copies.php');
    rmdir($tmp . '/diagnostics');
    unlink($tmp . '/vendor');
    rmdir($tmp);
});
$run = static function (string $arg) use ($tmp): string {
    $out = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($tmp . '/diagnostics/copies.php')
        . ' ' . escapeshellarg($arg) . ' 2>&1');
    return (string) $out;
};
$expected = "Definition: none\nAlice: demo-alice\nSame object: false\nBob: demo-bob\n"
    . "Alice tools: office_hours\nBob tools: none\n";
if ($run('') !== $expected) {
    throw new RuntimeException("diagnostic output changed:\n" . $run(''));
}
$refused = "Refused: This agent has no thread ID: bind one with setThreadId() first.\n";
if ($run('mistake') !== $expected . $refused) {
    throw new RuntimeException("the mistake branch was not refused:\n" . $run('mistake'));
}
// 3. The same facts in process: setters mutate, for() copies, collaborators stay shared.
require $lab . '/vendor/autoload.php';
foreach (['AssistantAgent', 'OfficeHours'] as $class) {
    if (!class_exists("App\\Neuron\\" . ($class === 'OfficeHours' ? 'Tools' : 'Agents') . "\\{$class}")) {
        require_once dirname(__DIR__, 2) . "/13-05-neuron-facade-alternative/files/start/{$class}.php";
    }
}
$fake = new FakeAIProvider(new AssistantMessage('Fake answer.'));
$definition = new AssistantAgent($fake);
$alice = $definition->for('demo-alice');
try {
    $alice->setThreadId('demo-bob');
    throw new LogicException('a bound copy was re-pointed');
} catch (WorkflowException) {
}
if ($alice->setThreadId('demo-alice') !== $alice || $definition->getThreadId() !== null) {
    throw new RuntimeException('setThreadId() must bind its receiver and accept the same id');
}
if ($alice->getProvider() !== $definition->getProvider()) {
    throw new RuntimeException('the copy no longer shares the provider');
}
$definition->addTool(new OfficeHours());
$late = $definition->for('demo-late');
if (count($late->getTools()) !== 1 || count($alice->getTools()) !== 0) {
    throw new RuntimeException('addTool() on the definition must reach later copies only');
}
$state = $alice->chat(new UserMessage('Hi'));
if ($state->getMessage()?->getContent() !== 'Fake answer.' || $fake->getCallCount() !== 1) {
    throw new RuntimeException('the bound copy did not run');
}
echo 'COPY NOT MUTATE OK' . PHP_EOL;
