<?php
declare(strict_types=1);
// Offline check of "Artisan generators" (FakeAIProvider: no model call, no keys).
// Usage: php verify-generators.php <lab>
// <lab> is the Laravel lab at the end of the lesson: AssistantAgent, OfficeHours and
// NormalizeQuestion generated with the SDK's Artisan commands, OfficeHours implemented and
// NormalizeQuestion's imports repaired as in this folder's files.
use App\Neuron\Agents\AssistantAgent;
use App\Neuron\Agents\Tools\OfficeHours;
use App\Neuron\Nodes\NormalizeQuestion;
use Illuminate\Contracts\Console\Kernel;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\WorkflowException;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\Tool;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;
$lab = realpath($argv[1] ?? '') ?: throw new RuntimeException('usage: verify-generators.php <lab>');
$fail = static function (string $what): never {
    throw new RuntimeException($what);
};
$same = static fn (string $a, string $b): bool => rtrim((string) file_get_contents($a), "\n") === rtrim((string) file_get_contents($b), "\n");
// 1. The lab holds the lesson's files, where the generators put them.
foreach ([
    'app/Neuron/Agents/AssistantAgent.php' => 'AssistantAgent.generated.php',
    'app/Neuron/Agents/Tools/OfficeHours.php' => 'OfficeHours.php',
    'app/Neuron/Nodes/NormalizeQuestion.php' => 'NormalizeQuestion.php',
] as $path => $file) {
    if (!is_file("{$lab}/{$path}") || !$same("{$lab}/{$path}", __DIR__ . "/{$file}")) {
        $fail("{$path} differs from files/{$file}");
    }
}
// 2. The SDK's generator conventions the lesson reads (segments 2, 4 and 6).
$sdk = $lab . '/vendor/neuron-core/neuron-laravel/src/Commands';
$namespaces = [
    'MakeAgent' => "'\\\\Neuron\\\\Agents'", 'MakeTool' => "'\\\\Neuron\\\\Agents\\\\Tools'",
    'MakeNode' => "'\\\\Neuron\\\\Nodes'", 'MakeRAG' => "'\\\\Neuron\\\\RAG'",
    'MakeWorkflow' => "'\\\\Neuron\\\\Workflows'", 'MakeMiddleware' => "'\\\\Neuron\\\\Middleware'",
];
foreach ($namespaces as $command => $namespace) {
    if (!str_contains((string) file_get_contents("{$sdk}/{$command}.php"), $namespace)) {
        $fail("{$command} no longer generates into {$namespace}");
    }
}
$rag = (string) file_get_contents($sdk . '/stubs/rag.stub');
$middleware = (string) file_get_contents($sdk . '/stubs/middleware.stub');
$workflow = (string) file_get_contents($sdk . '/stubs/workflow.stub');
if (!str_contains($rag, 'function embeddings(): EmbeddingsProviderInterface') || !str_contains($rag, 'function vectorStore(): VectorStoreInterface')) {
    $fail('rag.stub hooks changed: re-check segment 6');
}
if (!str_contains($middleware, 'public function before(') || !str_contains($middleware, 'public function after(')) {
    $fail('middleware.stub hooks changed: re-check segment 6');
}
if (!str_contains($workflow, 'protected function nodes(): array')) {
    $fail('workflow.stub changed: re-check segment 6b');
}
// 3. Boot the application with a known configuration (nothing read from .env matters).
require $lab . '/vendor/autoload.php';
$app = require $lab . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
// Approval lives on the tool and the tool node enforces it (segment 6).
$core = $lab . '/vendor/neuron-core/neuron-ai/src';
if (!method_exists(Tool::class, 'requireApproval')
    || !str_contains((string) file_get_contents($core . '/Agent/Nodes/ToolNode.php'), '->requiresApproval()')) {
    $fail('tool approval moved: re-check segment 6');
}
config()->set('neuron.provider.default', 'anthropic');
config()->set('neuron.provider.anthropic', ['key' => 'offline-check', 'model' => 'claude-offline', 'parameters' => []]);
// 4. The generated agent: the provider hook asks the facade for the configured default, the
//    instructions come from configuration, the tools hook is empty.
$agent = new AssistantAgent();
if (!$agent instanceof Agent || !$agent->getProvider() instanceof Anthropic) {
    $fail('the generated provider hook did not resolve the configured anthropic driver');
}
$instructions = (string) $agent->instructions();
if (!str_contains($instructions, 'You are a helpful AI assistant built with Neuron AI framework.')) {
    $fail('the generated instructions do not come from config/neuron.php');
}
$fake = new FakeAIProvider(new AssistantMessage('Hello from the fake.'));
$state = AssistantAgent::make()->setAiProvider($fake)->for('verify-generators')->chat(new UserMessage('Hi'));
if ($state->getMessage()?->getContent() !== 'Hello from the fake.' || $fake->getCallCount() !== 1) {
    $fail('the generated agent did not run on the fake provider');
}
// 5. The tool, called directly: no model, no arguments, the fixed published hours.
$tool = new OfficeHours();
if ($tool->getName() !== 'OfficeHours' || $tool->getDescription() !== 'Published support hours. Read only.') {
    $fail('OfficeHours identity: ' . $tool->getName() . ' / ' . $tool->getDescription());
}
if ($tool->getProperties() !== []) {
    $fail('OfficeHours still asks the model for arguments');
}
if ($tool() !== 'Monday to Friday, 9:00 to 17:00 CET.') {
    $fail('OfficeHours did not return the published hours');
}
// 6. The node: the generated imports fail validation, the repaired node runs in a bound workflow.
$generated = (string) file_get_contents(__DIR__ . '/NormalizeQuestion.start.php');
eval(str_replace(['<?php', 'declare(strict_types=1);', 'class NormalizeQuestion'], ['', '', 'class GeneratedNormalizeQuestion'], $generated));
try {
    Workflow::make()->addNode(new App\Neuron\Nodes\GeneratedNormalizeQuestion())->for('verify-node')->run();
    $fail('the generated node ran: the stub imports were fixed upstream, re-check segment 5');
} catch (WorkflowException $e) {
    if (!str_contains($e->getMessage(), 'must be a type that implements NeuronAI\Workflow\Events\Event')) {
        throw $e;
    }
}
try {
    Workflow::make()->addNode(new NormalizeQuestion())->run();
    $fail('a workflow ran without an identifier');
} catch (WorkflowException $e) {
    if (!str_contains($e->getMessage(), 'no workflow ID')) {
        throw $e;
    }
}
$result = Workflow::make()->addNode(new NormalizeQuestion())->for('check')->run();
if (!$result instanceof WorkflowState) {
    $fail('the repaired node did not run');
}
echo "GENERATORS OK\n";
