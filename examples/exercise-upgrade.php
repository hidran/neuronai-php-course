<?php
// Exercise solution: migrate a second outdated snippet and prove each repair offline.
// Run from the project root: php examples/exercise-upgrade.php (FakeAIProvider: no key, no model).
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use NeuronAI\Agent\Agent;
use NeuronAI\Agent\Observability\ToolCalled;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Tools\ToolProperty;

// The old snippet was:
//   $tool = Tool::make('slugify', 'Turn a title into a URL slug.')
//       ->addProperty(new ToolProperty('title', PropertyType::STRING, 'The title.', true))
//       ->setCallable(fn (string $title): string => ...);
//   $agent->observe(...); // listening to 'NeuronAI\Observability\Events\ToolCalled'
//   echo $agent->chat($message)->getContent();
final class SlugifyTool extends Tool
{
    protected string $name = 'slugify';
    protected ?string $description = 'Turn a title into a URL slug.';

    #[\Override]
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'title',
            type: PropertyType::STRING,
            description: 'The title.',
            required: true,
        )];
    }

    public function __invoke(string $title): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($title)), '-');
    }
}

function check(bool $ok, string $what): void
{
    echo ($ok ? 'ok   ' : 'FAIL ') . $what . PHP_EOL;
    if (!$ok) {
        exit(1);
    }
}

// 1. The tool runs directly, before any agent: same name, same input contract.
$tool = new SlugifyTool();
check($tool->getName() === 'slugify', 'the tool keeps its name');
check($tool('Version Strategy, Part 2') === 'version-strategy-part-2', 'the tool runs directly');

// 2. Behind an agent: the model asks for the tool once, then answers. The listener uses the installed event class.
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [new ToolCall('slugify', 'call-1', ['title' => 'Hello Neuron'])]),
    new AssistantMessage('The slug is hello-neuron.'),
);
$called = [];
$agent = Agent::make()->setAiProvider($fake)->addTool(new SlugifyTool())->for('exercise-1')
    ->subscribe(ToolCalled::class, function (ToolCalled $event) use (&$called): void {
        $called[] = $event->tool->getName();
    });
$state = $agent->chat(new UserMessage('Slug for "Hello Neuron"?'));
check($called === ['slugify'], 'the ToolCalled listener fired once');
check($state->getToolRuns('slugify') === 1, 'the state counts one slugify run');

// 3. The caller reads the state deliberately and refuses a missing message.
$text = $state->getMessage()?->getContent() ?? throw new RuntimeException('No message.');
check($text === 'The slug is hello-neuron.', 'the answer comes from getMessage()');
check($fake->getCallCount() === 2, 'two provider calls: the tool request and the answer');

echo 'Exercise complete.' . PHP_EOL;
