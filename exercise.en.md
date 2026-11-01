# Exercise: repair one more old snippet

Work in the same cumulative project. A colleague pasted this snippet from an older tutorial:

```php
$tool = Tool::make('slugify', 'Turn a title into a URL slug.')
    ->addProperty(new ToolProperty('title', PropertyType::STRING, 'The title.', true))
    ->setCallable(fn (string $title): string => /* ... */);
$agent->subscribe('NeuronAI\Observability\Events\ToolCalled', $listener);
echo $agent->chat($message)->getContent();
```

Before you change anything, run `composer show neuron-core/neuron-ai` and write down the installed release. Then open
the matching guides in `vendor/neuron-core/neuron-ai/upgrade/` (the tool guide, the AgentState guide and the
observability guide) and repair the three contracts:

1. A final `SlugifyTool` class that keeps the name `slugify`, the description and the `title` input. Lowercase the
   title, turn every run of other characters into one hyphen, trim the hyphens.
2. A listener on the event class the installed package really emits when a tool has run.
3. A caller that reads the answer from the returned state and refuses a missing message.

Prove each repair without a model. Use `FakeAIProvider` with two queued responses: a `ToolCallMessage` that asks for
`slugify` with the title "Hello Neuron", then an `AssistantMessage` with the final answer. Bind the agent to a thread
with `for()` before the chat.

Acceptance: the tool returns `version-strategy-part-2` for "Version Strategy, Part 2" when called directly; the listener
fires exactly once with the name `slugify`; `getToolRuns('slugify')` is 1; the answer text comes from `getMessage()`;
the fake provider was called twice. Then explain in two sentences why the old event name in the snippet would have left
your listener silent without any error.

The runnable reference is `examples/exercise-upgrade.php` (offline, no key needed).
