<?php
// Offline check of the AI-assisted development lesson (FakeAIProvider, no model call, no key, no network).
// Usage: php verify-assistant.php <lab>. Loads this folder's files; uses the lab only for vendor/ and the agent.
declare(strict_types=1);

$lab = $argv[1] ?? throw new RuntimeException('usage: verify-assistant.php <lab>');
require $lab . '/vendor/autoload.php';
if (!class_exists(App\Knowledge\SupportAgent::class)) {
    require_once __DIR__ . '/../../11-08b-choosing-knowledge-mechanisms/files/SupportAgent.php';
}
foreach (['Container', 'SupportQuestion', 'SupportAsk'] as $class) {
    require_once __DIR__ . '/' . $class . '.php';
}

use App\Knowledge\SupportAgent;
use App\Support\Container;
use App\Support\SupportAsk;
use App\Support\SupportQuestion;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\AgentState;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Testing\FakeEmbeddingsProvider;
use NeuronAI\Testing\FakeVectorStore;
use NeuronAI\Workflow\Workflow;

function check(bool $ok, string $what): void
{
    if (!$ok) {
        throw new RuntimeException('FAILED: ' . $what);
    }
}

function run(string $script, string ...$args): string
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script);
    foreach ($args as $arg) {
        $command .= ' ' . escapeshellarg($arg);
    }
    return (string) shell_exec($command . ' 2>&1');
}

function copyTree(string $from, string $to): void
{
    mkdir($to, 0777, true);
    foreach (scandir($from) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        is_dir("$from/$name") ? copyTree("$from/$name", "$to/$name") : copy("$from/$name", "$to/$name");
    }
}

// What the narration states about the installed package.
$core = $lab . '/vendor/neuron-core/neuron-ai';
check(str_contains((string) file_get_contents("$core/README.md"), 'npx --yes skills add ./vendor/neuron-core/neuron-ai/skills -y'),
    'the README documents the skills command');
foreach (['neuron-agent', 'neuron-tool', 'neuron-structured-output', 'neuron-rag', 'neuron-streaming', 'neuron-workflow',
    'neuron-test', 'neuron-laravel-integration', 'neuron-symfony-integration'] as $skill) {
    check(is_file("$core/skills/$skill/SKILL.md"), "skill $skill");
}
check(is_file("$core/skills/neuron-agent/references/workflow-extension.md"), 'neuron-agent ships references next to SKILL.md');
check((string) (new ReflectionMethod(Workflow::class, 'for'))->getReturnType() === 'static', 'for() returns a copy (static)');
check((string) (new ReflectionMethod(Agent::class, 'chat'))->getReturnType() === AgentState::class, 'chat() returns AgentState');
check((string) (new ReflectionMethod(Agent::class, 'stream'))->getReturnType() === 'Generator', 'stream() returns a Generator');

// The container: a fresh object per get(), an error for an unbound id.
$container = new Container();
$container->bind(stdClass::class, fn () => new stdClass());
check($container->get(stdClass::class) !== $container->get(stdClass::class), 'get() builds a fresh object');
try {
    $container->get(ArrayObject::class);
    check(false, 'an unbound id throws');
} catch (RuntimeException) {
}

// The reference change the assistant is asked for: validation, the bound copy, the answer, one provider call.
foreach (['', '   ', str_repeat('a', 501)] as $bad) {
    try {
        SupportQuestion::fromInput($bad);
        check(false, 'an invalid question is rejected');
    } catch (InvalidArgumentException) {
    }
}
check(SupportQuestion::fromInput('  Refund time?  ')->text === 'Refund time?', 'the question is trimmed');
$fake = new FakeAIProvider(new AssistantMessage('Five days.'));
$built = [];
$support = new Container();
$support->bind(SupportAgent::class, function () use ($fake, &$built): SupportAgent {
    return $built[] = new SupportAgent($fake, new FakeEmbeddingsProvider(), new FakeVectorStore(),
        new PDO('sqlite::memory:'), 'C-118');
});
$answer = new SupportAsk($support)(SupportQuestion::fromInput('Refund time?'));
check($answer === 'Five days.', 'the answer text is returned');
check($fake->getCallCount() === 1, 'one provider call');
check(count($built) === 1 && $built[0]->getThreadId() === null, 'for() bound a copy: the resolved agent stays unbound');
check(str_contains((string) file_get_contents(__DIR__ . '/SupportAsk.php'), 'random_bytes'), 'the server chooses the thread');
$command = (string) file_get_contents(__DIR__ . '/61-support-ask.php');
check(strpos($command, 'SupportQuestion::fromInput(') < strpos($command, "require __DIR__ . '/services.php'"),
    'the command validates the question before building any service');

// The guidance file typed on camera: short lines, no list markers (PhpStorm would continue a list on Enter).
$rules = file(__DIR__ . '/project-rules.md', FILE_IGNORE_NEW_LINES) ?: [];
check(count($rules) === 9, 'nine rule lines');
foreach ($rules as $line) {
    check(strlen($line) <= 70 && !preg_match('/^\s*([-*+]|\d+\.)\s/', $line) && $line !== '', "rule line: $line");
}
$text = implode("\n", $rules);
foreach (['AgentState', 'Generator', 'for()', 'FakeAIProvider', 'server'] as $needle) {
    check(str_contains($text, $needle), "the rules mention $needle");
}

// The docs MCP configuration and the request file.
$mcp = json_decode((string) file_get_contents(__DIR__ . '/mcp.json'), true);
check(($mcp['mcpServers']['neuron-docs']['url'] ?? '') === 'https://docs.neuron-ai.dev/~gitbook/mcp', 'docs MCP URL');
check(($mcp['mcpServers']['neuron-docs']['type'] ?? '') === 'http', 'docs MCP over HTTP');
$request = (string) file_get_contents(__DIR__ . '/support-command.txt');
$review = (string) file_get_contents(__DIR__ . '/61-review-change.php');
foreach (['src/Support/SupportQuestion.php', 'src/Support/SupportAsk.php', 'examples/61-support-ask.php',
    'tests/SupportAskTest.php'] as $allowed) {
    check(str_contains($request, $allowed) && str_contains($review, "'$allowed'"), "allowed file $allowed");
}

// The two prepared scripts, run in a throwaway project tree.
$tmp = sys_get_temp_dir() . '/verify-assistant-' . uniqid();
mkdir("$tmp/examples", 0777, true);
mkdir("$tmp/vendor/neuron-core/neuron-ai", 0777, true);
mkdir("$tmp/src/Support", 0777, true);
mkdir("$tmp/tests", 0777, true);
mkdir("$tmp/storage", 0777, true);
symlink("$core/skills", "$tmp/vendor/neuron-core/neuron-ai/skills");
copy(__DIR__ . '/61-skill-targets.php', "$tmp/examples/61-skill-targets.php");
copy(__DIR__ . '/61-review-change.php', "$tmp/examples/61-review-change.php");
copyTree("$core/skills/neuron-agent", "$tmp/.agents/skills/neuron-agent");
mkdir("$tmp/.claude/skills", 0777, true);
symlink('../../.agents/skills/neuron-agent', "$tmp/.claude/skills/neuron-agent");
copyTree("$core/skills/neuron-tool", "$tmp/.claude/skills/neuron-tool");
file_put_contents("$tmp/.claude/skills/neuron-tool/SKILL.md", "old text\n");
$targets = run("$tmp/examples/61-skill-targets.php");
check(str_contains($targets, '.agents/skills/neuron-agent: copy, same as vendor'), 'a copy is reported');
check(str_contains($targets, '.claude/skills/neuron-agent: link to ../../.agents/skills/neuron-agent, same as vendor'), 'a link is reported');
check(str_contains($targets, '.claude/skills/neuron-tool: copy, DIFFERENT from vendor'), 'a stale copy is reported');
check(str_contains($targets, 'Skill targets checked: 3'), 'three targets');
file_put_contents("$tmp/src/Support/Container.php", "<?php\n");
check(str_contains(run("$tmp/examples/61-review-change.php", '--baseline'), 'Baseline recorded:'), 'baseline');
file_put_contents("$tmp/src/Support/SupportAsk.php", "<?php\n");
file_put_contents("$tmp/src/Support/Container.php", "<?php\n// changed\n\$k = 'ANTHROPIC_KEY';\n");
$reviewed = run("$tmp/examples/61-review-change.php");
check(str_contains($reviewed, 'Changed: src/Support/SupportAsk.php (allowed)'), 'an allowed file is listed');
check(str_contains($reviewed, 'Changed: src/Support/Container.php (NOT ALLOWED)'), 'an unexpected change is flagged');
check(str_contains($reviewed, 'Credential references: 1'), 'a credential reference is counted');
check(str_contains($reviewed, 'Review complete: 2 files'), 'two changed files');
shell_exec('rm -rf ' . escapeshellarg($tmp));

echo 'ASSISTANT OK' . PHP_EOL;
