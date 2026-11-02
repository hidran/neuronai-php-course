<?php
declare(strict_types=1);
// Offline check of the lesson (FakeAIProvider: no model call, no network).
// Usage: php verify-boost-skills.php <lab>
// <lab> is the Laravel lab of the section. The SDK's Boost skills are compared
// with the installed core, the proposal is checked with a fake provider, and the
// older approval signature must be rejected by PHP. This folder's files are used,
// whatever state the lab is in.
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\Tool;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('lab path');
$sdk = $lab . '/vendor/neuron-core/neuron-laravel';
$core = $lab . '/vendor/neuron-core/neuron-ai';
// 1. Which SDK release is installed, and what its Boost skills say.
$installed = json_decode((string) file_get_contents($lab . '/vendor/composer/installed.json'), true);
$release = array_find($installed['packages'], fn (array $p) => $p['name'] === 'neuron-core/neuron-laravel')['version'] ?? '?';
$skills = '';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sdk . '/resources/boost/skills',
    FilesystemIterator::SKIP_DOTS)) as $file) {
    $skills .= file_get_contents((string) $file);
}
$stale = ['make(threadId:', 'setChatHistory', '->resume(', 'approvalPolicy(array $inputs)'];
$found = array_filter($stale, fn (string $form) => str_contains($skills, $form));
if (count($found) !== count($stale)) {
    throw new RuntimeException("neuron-laravel {$release}: the Boost skills no longer show "
        . implode(', ', array_diff($stale, $found)) . ' (scene 3 needs the 2.0.0 pin of prepare.sh)');
}
if (!is_file($core . '/skills/neuron-laravel-integration/SKILL.md')) {
    throw new RuntimeException('the core package ships no Laravel integration skill: re-check scene 3b');
}
// 2. The installed core contract the narration cites.
require $lab . '/vendor/autoload.php';
$constructor = new ReflectionMethod(NeuronAI\Workflow\Workflow::class, '__construct');
if ($constructor->getParameters()[0]->getName() !== 'workflowId'
    || !method_exists(Agent::class, 'setMessageStore')
    || method_exists(Agent::class, 'setChatHistory')
    || !method_exists(NeuronAI\Workflow\Workflow::class, 'submitInputs')
    || method_exists(NeuronAI\Workflow\Workflow::class, 'resume')) {
    throw new RuntimeException('the core contract changed: re-check scene 3');
}
$policy = new ReflectionMethod(Tool::class, 'approvalPolicy');
if ($policy->getNumberOfParameters() !== 0 || !method_exists(Tool::class, 'getInput')) {
    throw new RuntimeException('Tool::approvalPolicy() or getInput() changed: re-check scene 5');
}
// 3. The proposal: the imports and the constructor the narration reviews (no
// provider() hook, the course convention), and its test logic.
$proposal = file_get_contents(__DIR__ . '/start/SupportAgent.php');
foreach (['use NeuronAI\Agent\Agent;', 'use NeuronAI\Providers\AIProviderInterface;',
    'public function __construct(AIProviderInterface $provider)', 'parent::__construct();',
    '$this->setAiProvider($provider);'] as $needle) {
    if (!str_contains($proposal, $needle)) {
        throw new RuntimeException("the proposal lost: {$needle}");
    }
}
if (str_contains($proposal, 'function provider(')) {
    throw new RuntimeException('the proposal overrides provider(): inject the provider instead');
}
if (!class_exists(App\Neuron\Tools\OfficeHours::class)) {
    require_once dirname(__DIR__, 2) . '/13-05-neuron-facade-alternative/files/start/OfficeHours.php';
}
require_once __DIR__ . '/start/SupportAgent.php';
$fake = new FakeAIProvider(new AssistantMessage('We open at 9.'));
$agent = new App\Neuron\Agents\SupportAgent($fake);
$copy = $agent->for('ticket-42');
$state = $copy->chat(new UserMessage('When do you open?'));
if ($agent->getThreadId() !== null || $copy->getThreadId() !== 'ticket-42'
    || $state->getMessage()?->getContent() !== 'We open at 9.' || $fake->getCallCount() !== 1
    || $copy->getProvider() !== $fake || $copy->getTools()[0]->getName() !== 'office_hours') {
    throw new RuntimeException('the proposal does not bind a copy as its test claims');
}
$test = file_get_contents(__DIR__ . '/start/SupportAgentTest.php');
if (!str_contains($test, "\$copy = \$agent->for('ticket-42');") || !str_contains($test, 'new FakeAIProvider(')
    || !str_contains($test, '$agent = new SupportAgent($fake);')) {
    throw new RuntimeException('the proposal test changed');
}
// 4. The older approval signature is rejected when the class is declared.
$out = (string) shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg(
    'require ' . var_export($lab . '/vendor/autoload.php', true) . '; require '
    . var_export(__DIR__ . '/start/RefundTool.php', true) . ';'
) . ' 2>&1');
if (!str_contains($out, 'must be compatible with NeuronAI\Tools\Tool::approvalPolicy(): string|bool')
    && !str_contains($out, 'must be compatible with NeuronAI\Tools\Tool::approvalPolicy(): bool|string')) {
    throw new RuntimeException("the older signature was not rejected: {$out}");
}
echo "BOOST SKILLS OK (neuron-laravel {$release})" . PHP_EOL;
