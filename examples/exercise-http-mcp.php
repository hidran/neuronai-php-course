<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\HttpOfficeHoursAgent;
use App\Mcp\HttpOfficeConnection;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use Dotenv\Dotenv;
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
$base = 'http://127.0.0.1:48392';
$events = $base . '/mcp-events';
HttpOfficeConnection::discover($events);
$directory = dirname(__DIR__) . '/storage/http-office';
$before = glob($directory . '/*.json') ?: [];
$connector = HttpOfficeConnection::connector($events);
$connector->tools();
$created = array_values(array_diff(glob($directory . '/*.json'), $before));
check(count($created) === 1, 'Expected one new controlled session.');
unlink($created[0]);
$result = $connector->invokeTool(['name' => 'get_office_hours'],
    ['location' => 'south']);
$facts = json_decode($result->getText(), true, flags: JSON_THROW_ON_ERROR);
check(!$result->isError() && $facts['hours'] === '10:00-18:00',
    'Session recovery did not reach the fixture.');
echo 'SSE response and expired-session recovery: passed' . PHP_EOL;
$schemaFailed = false;
try {
    HttpOfficeConnection::discover($base . '/mcp-bad-schema');
} catch (ToolException) {
    $schemaFailed = true;
}
check($schemaFailed, 'Expected a schema conversion failure.');
$transportFailed = false;
try {
    HttpOfficeConnection::discover($base . '/not-a-protocol-endpoint');
} catch (McpException) {
    $transportFailed = true;
}
check($transportFailed, 'Expected a connection category failure.');
$legacyRejected = false;
try {
    McpConnector::make(['url' => $base . '/mcp', 'async' => true,
        'timeout' => 1])->only(['get_office_hours'])->tools();
} catch (McpException) {
    $legacyRejected = true;
}
check($legacyRejected, 'The fixture is not a legacy SSE server.');
echo 'Transport and schema failures distinguished: passed' . PHP_EOL;
echo 'Legacy selector is not a concurrency flag: passed' . PHP_EOL;
Dotenv::createImmutable(dirname(__DIR__))->load();
$view = new StreamProgress(diagnostic: true);
$stream = HttpOfficeHoursAgent::make()->endpoint($events)
    ->setThreadId('http-office-exercise')
    ->stream(new UserMessage('When is the south office open?'));
$state = ToolStreamConsumer::consume($stream, $view);
check($state !== null, 'SSE-framed HTTP agent run failed.');
check(in_array('get_office_hours', $view->completed(), true),
    'The HTTP capability did not run.');
$text = $state->getMessage()?->getContent() ?? '';
check(str_contains($text, '10:00') && str_contains($text, '18:00')
    && str_contains($text, 'Europe/Rome'), 'HTTP facts missing.');
echo 'South office over SSE-framed Streamable HTTP: passed' . PHP_EOL;
echo 'HTTP MCP exercise complete.' . PHP_EOL;
