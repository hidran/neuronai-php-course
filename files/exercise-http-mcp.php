<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OfficeHoursAgent;
use App\Config\ProviderFactory;
use App\Mcp\McpConnection;
use App\Mcp\OfficeEndpoints;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ToolException;
use NeuronAI\MCP\McpConnector;
use NeuronAI\MCP\McpException;
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function discover(string $url): array
{
    return McpConnection::http($url)->only(['get_office_hours'])->tools();
}
$settings = require __DIR__ . '/bootstrap.php';
check(count(discover(OfficeEndpoints::EVENTS)) === 1,
    'The SSE framed endpoint must publish the office hours tool.');
$directory = dirname(__DIR__) . '/storage/http-office';
$before = glob($directory . '/*.json') ?: [];
$connection = McpConnection::http(OfficeEndpoints::EVENTS)
    ->only(['get_office_hours']);
$connection->tools();
$created = array_values(array_diff(glob($directory . '/*.json'), $before));
check(count($created) === 1, 'Expected one new controlled session.');
unlink($created[0]);
$result = $connection->connector()->invokeTool(
    ['name' => 'get_office_hours'], ['location' => 'south']);
$facts = json_decode($result->getText(), associative: true,
    flags: JSON_THROW_ON_ERROR);
check(!$result->isError() && $facts['hours'] === '10:00-18:00',
    'Session recovery did not reach the fixture.');
echo 'SSE response and expired session recovery: passed' . PHP_EOL;
$schemaFailed = false;
try {
    discover(OfficeEndpoints::BAD_SCHEMA);
} catch (ToolException) {
    $schemaFailed = true;
}
check($schemaFailed, 'Expected a schema conversion failure.');
$transportFailed = false;
try {
    discover(OfficeEndpoints::NOT_PROTOCOL);
} catch (McpException) {
    $transportFailed = true;
}
check($transportFailed, 'Expected a connection category failure.');
$legacyRejected = false;
try {
    McpConnector::make(['url' => OfficeEndpoints::MCP, 'async' => true,
        'timeout' => 1])->only(['get_office_hours'])->tools();
} catch (McpException) {
    $legacyRejected = true;
}
check($legacyRejected, 'The fixture is not a legacy SSE server.');
echo 'Transport and schema failures distinguished: passed' . PHP_EOL;
echo 'Legacy selector is not a concurrency flag: passed' . PHP_EOL;
$provider = new ProviderFactory($settings)->create();
$view = new StreamProgress(diagnostic: true);
$stream = OfficeHoursAgent::make($provider, $connection->connector())
    ->setThreadId('http-office-exercise')
    ->stream(new UserMessage('When is the south office open?'));
$state = new ToolStreamConsumer($view)->consume($stream);
check($state !== null, 'SSE framed HTTP agent run failed.');
check(in_array('get_office_hours', $view->completed(), true),
    'The HTTP capability did not run.');
echo 'South office over SSE framed Streamable HTTP: passed' . PHP_EOL;
echo 'HTTP MCP exercise complete.' . PHP_EOL;
