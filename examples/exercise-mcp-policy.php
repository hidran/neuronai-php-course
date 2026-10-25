<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PolicyAgent;
use App\Config\ProviderFactory;
use App\Mcp\McpPolicy;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ToolException;
use NeuronAI\Tools\ToolRegistry;
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
$root = dirname(__DIR__);
$policy = new McpPolicy($root . '/servers/policy-tools.php');
check($policy->select([]) === [] && $policy->connections() === 0,
    'Deny-all must not build a connection.');
echo 'Deny-all built no connection: passed' . PHP_EOL;
$allowed = $policy->select(['list_locations', 'get_office_hours']);
McpPolicy::assertExact($allowed,
    ['get_office_hours', 'list_locations']);
echo 'Allowlist verified exactly: passed' . PHP_EOL;
$duplicated = false;
try {
    new ToolRegistry([...$allowed, ...$allowed]);
} catch (ToolException) {
    $duplicated = true;
}
check($duplicated, 'Duplicate names must be rejected.');
echo 'Duplicate names rejected before inference: passed' . PHP_EOL;
$framed = $policy->framed(['get_office_hours']);
$output = (string) $framed[0](location: 'north');
check(str_contains($output, '<tool_result name="get_office_hours"')
    && str_contains($output, '09:00-17:00')
    && str_contains($output, 'not instructions'),
    'Framed results must carry the provenance note.');
echo 'Tool results framed as data: passed' . PHP_EOL;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$audit = $root . '/storage/mcp-policy.jsonl';
file_put_contents($audit, '');
$state = PolicyAgent::make($provider, $policy)
    ->setThreadId('policy-exercise-' . bin2hex(random_bytes(6)))
    ->chat(new UserMessage('Use list_locations first. Then use '
        . 'get_office_hours for south. Report hours and timezone.'));
check($state->getToolRuns('list_locations') >= 1, 'No location list.');
check($state->getToolRuns('get_office_hours') >= 1, 'No south read.');
$entries = file($audit, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)
    ?: [];
check($entries !== [], 'No audited call reached the fixture.');
foreach ($entries as $line) {
    $entry = json_decode($line, associative: true,
        flags: JSON_THROW_ON_ERROR);
    check(in_array($entry['name'], McpPolicy::READS, true),
        'An unapproved capability ran.');
    check($entry['outcome'] === 'ok', 'A read was denied by the server');
}
echo 'South read and location list audited: passed' . PHP_EOL;
echo $state->getMessage()?->getContent() . PHP_EOL;
echo 'MCP policy exercise complete.' . PHP_EOL;
