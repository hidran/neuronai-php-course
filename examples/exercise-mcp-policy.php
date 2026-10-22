<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PolicyAgent;
use App\Mcp\McpPolicy;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ToolException;
use NeuronAI\Tools\ToolRegistry;
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
McpPolicy::$factories = 0;
$denied = McpPolicy::select([]);
check($denied === [] && McpPolicy::$factories === 0,
    'Deny-all must not build a connector.');
echo 'Deny-all built no connector: passed' . PHP_EOL;
$allowed = McpPolicy::select(['list_locations', 'get_office_hours']);
McpPolicy::assertExact($allowed, ['get_office_hours', 'list_locations']);
check(count($allowed) === 2, 'Allowlist did not keep exactly two reads.');
echo 'Allowlist verified exactly: passed' . PHP_EOL;
$duplicated = false;
try {
    new ToolRegistry([...$allowed, ...$allowed]);
} catch (ToolException) {
    $duplicated = true;
}
check($duplicated, 'Duplicate names must be rejected.');
$second = McpPolicy::select(['maintenance_preview', 'get_office_hours']);
$merged = [...$allowed];
$seen = McpPolicy::names($merged);
foreach ($second as $tool) {
    if (!in_array($tool->getName(), $seen, true)) {
        $merged[] = $tool;
        $seen[] = $tool->getName();
    }
}
$registry = new ToolRegistry($merged);
McpPolicy::assertExact($registry->all(),
    ['get_office_hours', 'list_locations', 'maintenance_preview']);
echo 'Duplicate names rejected before inference: passed' . PHP_EOL;
$root = dirname(__DIR__);
Dotenv::createImmutable($root)->load();
$audit = $root . '/storage/mcp-policy.jsonl';
file_put_contents($audit, '');
$state = PolicyAgent::make()
    ->setThreadId('policy-exercise-' . bin2hex(random_bytes(6)))
    ->chat(new UserMessage('Use list_locations first. Then use get_office_hours '
        . 'for south. Report exact hours and timezone.'));
check($state->getToolRuns('list_locations') >= 1, 'The location list did not run.');
check($state->getToolRuns('get_office_hours') >= 1, 'The south read did not run.');
$reply = $state->getMessage()?->getContent() ?? '';
foreach (['10:00', '18:00', 'Europe/Rome'] as $fact) {
    check(str_contains($reply, $fact), 'A server fact is missing from the answer.');
}
$entries = file($audit, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
check($entries !== [], 'No audited call reached the fixture.');
$names = [];
foreach ($entries as $line) {
    $entry = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
    check(in_array($entry['name'], McpPolicy::READS, true),
        'An unapproved capability ran.');
    $names[] = $entry['name'];
    if ($entry['name'] === 'get_office_hours') {
        check($entry['arguments'] === ['location' => 'south'],
            'The south read carried unexpected data.');
    } else {
        check($entry['arguments'] === [],
            'The location list carried context.');
    }
}
check(in_array('list_locations', $names, true)
    && in_array('get_office_hours', $names, true), 'Expected both reads.');
echo 'South read and location list completed: passed' . PHP_EOL;
echo $reply . PHP_EOL;
echo 'MCP policy exercise complete.' . PHP_EOL;
