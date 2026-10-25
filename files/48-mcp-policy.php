<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PolicyAgent;
use App\Config\ProviderFactory;
use App\Mcp\McpPolicy;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ToolException;
use NeuronAI\Tools\ToolRegistry;
use NeuronAI\Workflow\WorkflowStatus;
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function show(string $label, array $tools): void
{
    echo $label . ': ' . implode(', ', McpPolicy::names($tools))
        . ' | count ' . count($tools) . PHP_EOL;
}
$root = dirname(__DIR__);
$policy = new McpPolicy($root . '/servers/policy-tools.php');
$audit = $root . '/storage/mcp-policy.jsonl';
$mode = $argv[1] ?? '--compare';
if ($mode === '--compare') {
    $old = $policy->connection()->tools();
    $new = $policy->connection(['--expanded'])->tools();
    $allowed = $policy->select(McpPolicy::READS, ['--expanded']);
    show('Everything before the server grew', $old);
    show('Everything after the server grew', $new);
    show('Only our allowlist after it grew', $allowed);
    check(count($old) === 3 && count($new) === 4, 'Server did not grow');
    $before = $policy->connections();
    check($policy->select([]) === []
        && $policy->connections() === $before,
        'Deny-all constructed a connection.');
    echo 'Application deny-all: count 0, new connections 0' . PHP_EOL;
    echo 'Discovery comparison complete.' . PHP_EOL;
} elseif ($mode === '--compatibility') {
    $typo = ['get_office_hour', 'list_locations'];
    $tools = $policy->connection()->only($typo)->tools();
    show('Literal typo result', $tools);
    try {
        McpPolicy::assertExact($tools, $typo);
        throw new LogicException('Missing name was not detected.');
    } catch (RuntimeException) {
        echo 'Missing required name detected: yes' . PHP_EOL;
    }
    foreach ([['--unsupported'], ['--broken-read']] as $flags) {
        try {
            $policy->select(['get_office_hours'], $flags);
            check($flags === ['--unsupported'], 'Broken schema accepted');
        } catch (ToolException) {
            check($flags === ['--broken-read'], 'Unrelated schema broke');
        }
    }
    echo 'Unrelated unsupported schema filtered: yes' . PHP_EOL;
    echo 'Selected broken schema rejected: yes' . PHP_EOL;
    echo 'Compatibility checks complete.' . PHP_EOL;
} elseif ($mode === '--permissions') {
    $tools = $policy->connection(['--north-only'])
        ->only(['get_office_hours', 'maintenance_preview'])->tools();
    $byName = array_combine(McpPolicy::names($tools), $tools);
    $hours = $byName['get_office_hours'];
    check(!$hours(location: 'north')->isError(), 'North was refused.');
    check($hours(location: 'south')->isError(), 'South was allowed.');
    check($hours(location: 42)->isError(), 'Wrong type was allowed.');
    check($byName['maintenance_preview']()->isError(),
        'Read-only identity ran maintenance.');
    echo 'North accepted; south rejected by server: yes' . PHP_EOL;
    echo 'Wrong type rejected by server: yes' . PHP_EOL;
    echo 'Read-only identity denied maintenance: yes' . PHP_EOL;
    echo 'Permission checks complete.' . PHP_EOL;
} elseif ($mode === '--metadata') {
    $before = $policy->select(McpPolicy::READS);
    $after = $policy->select(McpPolicy::READS, ['--changed-description']);
    check($before[0]->getDescription()
        !== $after[0]->getDescription(),
        'Description did not change.');
    echo 'Allowed names unchanged: yes' . PHP_EOL;
    echo 'Description changed under the same name: yes' . PHP_EOL;
    echo 'Names do not pin descriptions or behavior.' . PHP_EOL;
    echo 'Metadata checks complete.' . PHP_EOL;
} elseif ($mode === '--collisions') {
    $first = $policy->select(McpPolicy::READS);
    $second = $policy->select(['get_office_hours']);
    try {
        new ToolRegistry([...$first, ...$second]);
        throw new LogicException('Duplicate names were not rejected.');
    } catch (ToolException) {
        echo 'Duplicate names rejected before inference: yes' . PHP_EOL;
    }
    $registry = new ToolRegistry($first);
    show('Resolved registry', $registry->all());
    echo 'Collision checks complete.' . PHP_EOL;
} elseif ($mode === '--invoke' || $mode === '--approval') {
    $settings = require __DIR__ . '/bootstrap.php';
    $provider = new ProviderFactory($settings)->create();
    file_put_contents($audit, '');
    $maintenance = $mode === '--approval';
    $agent = PolicyAgent::make($provider, $policy, $maintenance)
        ->setThreadId('policy-' . bin2hex(random_bytes(8)));
    show('Offered tools', $agent->getTools());
    $question = $mode === '--approval'
        ? 'Run the maintenance preview now.'
        : 'Use get_office_hours for north. Report hours and timezone.';
    $state = $agent->chat(new UserMessage($question));
    if ($state->getStatus() === WorkflowStatus::Suspended) {
        foreach ($agent->pendingApprovals() as $action) {
            echo "Pending approval: {$action->name} "
                . $action->description . PHP_EOL;
        }
        $decisions = array_fill_keys(array_map(
            static fn ($action): string => $action->id,
            $agent->pendingApprovals(),
        ), 'approve');
        echo 'Approving ' . count($decisions) . ' call(s)...' . PHP_EOL;
        $state = $agent->submitApprovalDecisions($decisions)->run();
    }
    echo 'Answer: ' . $state->getMessage()?->getContent() . PHP_EOL;
    foreach (file($audit, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
        ?: [] as $line) {
        echo 'Audit: ' . $line . PHP_EOL;
    }
    echo 'Policy run complete.' . PHP_EOL;
} else {
    throw new RuntimeException('Unknown demo mode.');
}
