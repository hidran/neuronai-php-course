<?php
declare(strict_types=1);
// Offline check of the lesson (FakeAIProvider: no model call, no network).
// Usage: php verify-facade-alternative.php <lab>
// <lab> is the Laravel lab of the section (neuron-laravel 2.x, NeuronAI 4.1).
// This folder's classes are loaded before the lab's, so the check follows the
// lesson's files whatever state the lab is in.
use Illuminate\Contracts\Console\Kernel;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolCall;
use Symfony\Component\Console\Output\BufferedOutput;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('lab path');
foreach ([
    'NEURON_AI_PROVIDER' => 'anthropic',
    'ANTHROPIC_KEY' => 'offline-check',
    'LOG_CHANNEL' => 'null',
] as $name => $value) {
    putenv("{$name}={$value}");
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}
// 1. What the narration reads in the installed packages.
$sdk = $lab . '/vendor/neuron-core/neuron-laravel/src';
$core = $lab . '/vendor/neuron-core/neuron-ai/src';
$neuron = file_get_contents($sdk . '/Neuron.php');
if (!str_contains($neuron, 'private function makeAgent(): Agent')
    || !str_contains($neuron, '$agent = Agent::make();')
    || !str_contains($neuron, 'return $this->makeAgent()->chat($message);')
    || !str_contains($neuron, 'return $this->makeAgent()->stream($message);')
    || str_contains($neuron, 'function for(')
    || str_contains($neuron, 'setThreadId')) {
    throw new RuntimeException('the SDK Neuron class changed: re-check scenes 2b and 2c');
}
$nodes = array_map('basename', glob($core . '/Agent/Nodes/*.php'));
foreach (['ChatNode.php', 'StructuredOutputNode.php', 'ToolNode.php'] as $node) {
    if (!in_array($node, $nodes, true)) {
        throw new RuntimeException("{$node} is missing: re-check scene 5b");
    }
}
if (array_find($nodes, fn (string $n) => str_contains($n, 'Stream')) !== null) {
    throw new RuntimeException('a streaming node exists now: re-check scene 5b');
}
require $lab . '/vendor/autoload.php';
$policy = new ReflectionMethod(Tool::class, 'approvalPolicy');
if ($policy->getNumberOfParameters() !== 0) {
    throw new RuntimeException('Tool::approvalPolicy() takes parameters now: re-check scene 6');
}
// 2. The lesson's classes first, then the lab's application.
foreach (['AssistantAgent', 'OfficeHours', 'AuditMiddleware', 'SupportHours'] as $class) {
    require_once __DIR__ . "/start/{$class}.php";
}
require_once __DIR__ . '/Diagnose.php';
$app = require $lab . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$run = static function (string $path, FakeAIProvider $fake) use ($app, $kernel): string {
    $app->instance(AIProviderInterface::class, $fake);
    $output = new BufferedOutput();
    ob_start();
    $code = $kernel->call('diagnose', ['path' => $path], $output);
    $echoed = (string) ob_get_clean();
    if ($code !== 0) {
        throw new RuntimeException("diagnose {$path} exited with {$code}");
    }
    return $echoed . $output->fetch();
};
// 3. The facade: refused before any model call, for lack of a thread.
$fake = new FakeAIProvider();
$out = $run('facade', $fake);
if (!str_contains($out, 'Facade: This agent has no thread ID')
    || str_contains($out, 'The facade answered.')) {
    throw new RuntimeException("facade path: {$out}");
}
// 4. The named class through the container, bound with for().
$answer = 'Support is open Monday to Friday, 9:00 to 17:00 CET.';
$fake = new FakeAIProvider(new AssistantMessage($answer));
$out = $run('chat', $fake);
if (!str_contains($out, "Answer: {$answer}") || $fake->getCallCount() !== 1) {
    throw new RuntimeException("chat path: {$out}");
}
// 5. Stream: text chunks only, then the generator's return value.
$fake = new FakeAIProvider(new AssistantMessage($answer));
$out = $run('stream', $fake);
if (!str_contains($out, $answer) || !str_contains($out, 'NeuronAI\Agent\AgentState')
    || $fake->getRecorded()[0]->method !== 'stream') {
    throw new RuntimeException("stream path: {$out}");
}
// 6. Structured: the populated output object.
$json = '{"days":"Monday to Friday","opens":"09:00","closes":"17:00"}';
$fake = new FakeAIProvider(new AssistantMessage($json));
$out = $run('structured', $fake);
if (!str_contains($out, 'From 09:00 to 17:00')
    || $fake->getRecorded()[0]->method !== 'structured') {
    throw new RuntimeException("structured path: {$out}");
}
// 7. The tool attached to the bound copy runs, and prints its own label.
$call = new ToolCallMessage(null, [new ToolCall('office_hours', 'call_1', [])]);
$fake = new FakeAIProvider($call, new AssistantMessage($answer));
$out = $run('tool', $fake);
if (!str_contains($out, '[office_hours] tool ran') || !str_contains($out, "Answer: {$answer}")
    || $fake->getCallCount() !== 2) {
    throw new RuntimeException("tool path: {$out}");
}
// 8. The container gives a fresh, unbound agent each time: for() binds a copy.
$agent = $app->make(App\Neuron\Agents\AssistantAgent::class);
$copy = $agent->for('verify');
if (!$agent instanceof Agent || $agent->getThreadId() !== null || $copy->getThreadId() !== 'verify'
    || $app->make(App\Neuron\Agents\AssistantAgent::class) === $agent) {
    throw new RuntimeException('the container or for() changed');
}
echo 'FACADE ALTERNATIVE OK' . PHP_EOL;
