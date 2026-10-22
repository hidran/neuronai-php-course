<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OfficeHoursAgent;
use App\Mcp\OfficeHoursConnection;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
function check(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}
$tools = OfficeHoursConnection::discover();
check(count($tools) === 1, 'Expected exactly one discovered tool.');
$connector = OfficeHoursConnection::connector();
$result = $connector->invokeTool(['name' => 'get_office_hours'],
    ['location' => 'south']);
check(!$result->isError(), 'The south fixture unexpectedly failed.');
$facts = json_decode($result->getText(), true, flags: JSON_THROW_ON_ERROR);
check($facts === ['location' => 'south', 'hours' => '10:00-18:00',
    'timezone' => 'Europe/Rome'], 'Unexpected south fixture.');
$unknown = $connector->invokeTool(['name' => 'get_office_hours'],
    ['location' => 'missing']);
check($unknown->isError(), 'Unknown location needs a tool error.');
$invalid = $connector->invokeTool(['name' => 'get_office_hours'],
    ['location' => 123]);
check($invalid->isError(), 'Invalid input needs a tool error.');
$none = OfficeHoursConnection::connector()->only(['misspelled'])->tools();
check($none === [], 'A mismatched allowlist must not invent tools.');
echo 'Direct south invocation and error paths: passed' . PHP_EOL;
echo 'Misspelled allowlist discovered no tools: passed' . PHP_EOL;
Dotenv::createImmutable(dirname(__DIR__))->load();
$view = new StreamProgress(diagnostic: true);
$stream = OfficeHoursAgent::make()->setThreadId('office-exercise')
    ->stream(new UserMessage('When is the south office open?'));
$state = ToolStreamConsumer::consume($stream, $view);
check($state !== null, 'Live agent run failed.');
check(in_array('get_office_hours', $view->completed(), true),
    'The discovered capability did not run.');
$reply = $state->getMessage()?->getContent() ?? '';
check(str_contains($reply, '10:00') && str_contains($reply, '18:00')
    && str_contains($reply, 'Europe/Rome'), 'Server facts missing.');
echo 'South office grounded in an actual tool result: passed' . PHP_EOL;
echo 'Local MCP exercise complete.' . PHP_EOL;
