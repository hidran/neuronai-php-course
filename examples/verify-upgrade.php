<?php
// Offline check of the version strategy lesson (FakeAIProvider, no model call, no key).
// Usage: php verify-upgrade.php <lab>. Loads this folder's classes, never the lab's copies.
declare(strict_types=1);

$lab = $argv[1] ?? throw new RuntimeException('usage: verify-upgrade.php <lab>');
require $lab . '/vendor/autoload.php';
foreach (['StreamedAnswer', 'AssistantCalls', 'WordCountTool', 'NotesAgent', 'ApprovalResume'] as $class) {
    require_once __DIR__ . '/' . $class . '.php';
}

use App\Upgrade\ApprovalResume;
use App\Upgrade\AssistantCalls;
use App\Upgrade\NotesAgent;
use App\Upgrade\WordCountTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\AgentState;
use NeuronAI\Agent\Observability\InferenceStart;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\Tool;
use NeuronAI\Workflow\Events\StartEvent;
use NeuronAI\Workflow\Events\StopEvent;
use NeuronAI\Workflow\Interrupt\WaitForEventRequest;
use NeuronAI\Workflow\Node;
use NeuronAI\Workflow\Workflow;
use NeuronAI\Workflow\WorkflowState;

function check(bool $ok, string $what): void
{
    if (!$ok) {
        throw new RuntimeException('FAILED: ' . $what);
    }
}

// What the narration says about the installed contracts.
check((new ReflectionClass(Tool::class))->isAbstract(), 'Tool is abstract');
check(!class_exists('NeuronAI\Agent') && !class_exists('NeuronAI\SystemPrompt'), 'old short namespaces are gone');
check(class_exists(NeuronAI\Agent\SystemPrompt::class), 'SystemPrompt lives under Agent');
$chat = new ReflectionMethod(Agent::class, 'chat');
check((string) $chat->getReturnType() === AgentState::class, 'chat() returns AgentState');
check((string) (new ReflectionMethod(Agent::class, 'stream'))->getReturnType() === 'Generator', 'stream() returns a Generator');
check((string) (new ReflectionMethod(AgentState::class, 'getMessage'))->getReturnType() === '?NeuronAI\Chat\Messages\Message', 'getMessage() may return null');
$constructor = (new ReflectionMethod(Workflow::class, '__construct'))->getParameters();
check($constructor[0]->getName() === 'workflowId', 'the core constructor takes a workflow ID');
check(method_exists(Agent::class, 'messageStore') && !method_exists(Agent::class, 'chatHistory'), 'the memory hook is messageStore()');
check(!class_exists('NeuronAI\Chat\History\FileChatHistory'), 'FileChatHistory is gone');
check(method_exists(Workflow::class, 'for') && method_exists(Workflow::class, 'submitInputs'), 'for() and submitInputs() exist');
check(!method_exists(Workflow::class, 'resume'), 'Workflow has no resume()');
check(method_exists(NeuronAI\Workflow\Executor\ExecutionRequest::class, 'resume'), 'ExecutionRequest::resume() exists');
foreach (['0-reinstall-agent-skills', '3-tool-is-now-abstract', '13-bind-a-workflow-id', '15-workflow-interruptions',
    '23-agent-returns-agent-state', '31-message-store', '46-observability'] as $guide) {
    check(is_file($lab . "/vendor/neuron-core/neuron-ai/upgrade/{$guide}.md"), "upgrade guide {$guide}");
}

// The repaired code, as the tests on screen check it.
$agent = static fn (FakeAIProvider $fake): NotesAgent => new NotesAgent($fake, new InMemoryMessageStore())->for('u-1');
$fake = new FakeAIProvider(new AssistantMessage('A trait.'));
check(new AssistantCalls($agent($fake))->answer('What is a trait?') === 'A trait.', 'answer() maps the content');
check($fake->getCallCount() === 1, 'one provider call');
$fake = new FakeAIProvider(new AssistantMessage('An enum.'));
$fake->setStreamChunkSize(3);
$streamed = new AssistantCalls($agent($fake))->streamed('And an enum?');
check($streamed->text === 'An enum.' && $streamed->textChunks === 3, 'stream text and chunks');
check($streamed->state instanceof AgentState, 'getReturn() is the AgentState');
$tool = new WordCountTool();
check($tool->getName() === 'word_count' && $tool('one two three') === 3, 'WordCountTool contract');
$definition = new NotesAgent(new FakeAIProvider(), new InMemoryMessageStore());
$bound = $definition->for('notes-42');
check($definition->getThreadId() === null && $bound->getThreadId() === 'notes-42', 'for() returns a bound copy');
$dir = sys_get_temp_dir() . '/verify-upgrade-' . uniqid();
mkdir($dir);
$fake = new FakeAIProvider(new AssistantMessage('Noted.'));
new NotesAgent($fake, new FileMessageStore($dir))->for('notes-42')->chat(new UserMessage('We deploy on Friday.'));
$fresh = new NotesAgent($fake, new FileMessageStore($dir))->for('notes-42');
check(count($fresh->getChatHistory()->getMessages()) === 2, 'stored messages reach a fresh instance');
array_map('unlink', glob($dir . '/*') ?: []);
rmdir($dir);
$seen = ['new' => 0, 'old' => 0];
$listening = $agent(new FakeAIProvider(new AssistantMessage('Yes.'), new AssistantMessage('Yes.')))
    ->subscribe(InferenceStart::class, function () use (&$seen): void { $seen['new']++; })
    ->subscribe('NeuronAI\Observability\Events\InferenceStart', function () use (&$seen): void { $seen['old']++; });
$listening->chat(new UserMessage('Listening?'));
check($seen === ['new' => 1, 'old' => 0], 'the installed event fires, the old name never does');

// The workflow continuation: the pause is returned state, the answer is staged with submitInputs().
final class ApprovalNode extends Node
{
    public function __invoke(StartEvent $event, WorkflowState $state): StopEvent
    {
        $answer = $this->interrupt(new WaitForEventRequest('approval'));
        $state->set('approved', $answer['approved'] ?? false);
        return new StopEvent();
    }
}
$workflow = Workflow::make()->addNode(new ApprovalNode())->setWorkflowId('approval-1');
$resume = new ApprovalResume($workflow);
$paused = $resume->start();
check($paused->isInterrupted(), 'run() returns an interrupted state');
$done = $resume->answer(['approved' => true]);
check(!$done->isInterrupted() && $done->get('approved') === true, 'submitInputs()->run() delivers the answer');

// The fixtures stay outdated: they must keep the shapes the narration points at.
$legacy = __DIR__ . '/legacy';
check(str_contains(file_get_contents("$legacy/old-tool.php"), "Tool::make('word_count'"), 'old tool fixture');
check(str_contains(file_get_contents("$legacy/old-tool.php"), '->setCallable('), 'old tool callable');
check(str_contains(file_get_contents("$legacy/old-callers.php"), '$stream->events()'), 'old stream caller');
check(str_contains(file_get_contents("$legacy/old-callers.php"), 'use NeuronAI\Agent;'), 'old import');
check(str_contains(file_get_contents("$legacy/prerelease-agent.php"), 'make(threadId:'), 'prerelease make');
check(str_contains(file_get_contents("$legacy/old-resume.php"), '$workflow->resume('), 'old resume');

echo 'UPGRADE OK' . PHP_EOL;
