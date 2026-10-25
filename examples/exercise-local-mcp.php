<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OfficeHoursAgent;
use App\Config\ProviderFactory;
use App\Mcp\McpConnection;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use NeuronAI\Chat\Messages\UserMessage;
function check(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}
$settings = require __DIR__ . '/bootstrap.php';
$server = dirname(__DIR__) . '/servers/office-hours.php';
$connection = McpConnection::local($server)->only(['get_office_hours']);
$tools = $connection->tools();
check(count($tools) === 1, 'Expected exactly one discovered tool.');
$connector = $connection->connector();
$result = $connector->invokeTool(['name' => 'get_office_hours'],
    ['location' => 'south']);
check(!$result->isError(), 'The south fixture unexpectedly failed.');
$facts = json_decode($result->getText(), associative: true,
    flags: JSON_THROW_ON_ERROR);
check($facts === ['location' => 'south', 'hours' => '10:00-18:00',
    'timezone' => 'Europe/Rome'], 'Unexpected south fixture.');
$unknown = $connector->invokeTool(['name' => 'get_office_hours'],
    ['location' => 'missing']);
check($unknown->isError(), 'Unknown location needs a tool error.');
$invalid = $connector->invokeTool(['name' => 'get_office_hours'],
    ['location' => 123]);
check($invalid->isError(), 'Invalid input needs a tool error.');
$none = McpConnection::local($server)->only(['misspelled'])->tools();
check($none === [], 'A mismatched allowlist must not invent tools.');
echo 'Direct south invocation and error paths: passed' . PHP_EOL;
echo 'Misspelled allowlist discovered no tools: passed' . PHP_EOL;
$provider = new ProviderFactory($settings)->create();
$view = new StreamProgress(diagnostic: true);
$stream = OfficeHoursAgent::make($provider, $connector)
    ->setThreadId('office-exercise')
    ->stream(new UserMessage('When is the south office open?'));
$state = new ToolStreamConsumer($view)->consume($stream);
check($state !== null, 'Live agent run failed.');
check(in_array('get_office_hours', $view->completed(), true),
    'The discovered capability did not run.');
echo 'South office grounded in an actual tool result: passed' . PHP_EOL;
echo 'Local MCP exercise complete.' . PHP_EOL;
