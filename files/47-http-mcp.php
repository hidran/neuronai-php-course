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
use NeuronAI\MCP\McpException;
$settings = require __DIR__ . '/bootstrap.php';
$started = hrtime(true);
$connection = McpConnection::http(OfficeEndpoints::MCP)
    ->only(['get_office_hours']);
try {
    $tools = $connection->tools();
} catch (McpException | ToolException $error) {
    $category = $error instanceof McpException ? 'transport' : 'schema';
    fwrite(STDERR, 'Integration category: ' . $category . PHP_EOL);
    echo 'Office hours service unavailable.' . PHP_EOL;
    exit(2);
}
foreach ($tools as $tool) {
    echo 'HTTP discovered: ' . $tool->getName() . PHP_EOL;
    echo 'HTTP input: ' . implode(', ',
        array_keys($tool->getInputSchema()['properties'])) . PHP_EOL;
}
echo 'HTTP discovery ms: ' . round((hrtime(true) - $started) / 1e6)
    . PHP_EOL;
if (($argv[1] ?? '') === '--discover') {
    echo 'HTTP discovery complete.' . PHP_EOL;
    exit;
}
$provider = new ProviderFactory($settings)->create();
$agent = OfficeHoursAgent::make($provider, $connection->connector())
    ->setThreadId('http-office-demo');
$view = new StreamProgress(diagnostic: true);
$stream = $agent->stream(
    new UserMessage('When is the north office open?')
);
$state = new ToolStreamConsumer($view)->consume($stream);
if ($state === null) {
    exit(1);
}
if (!in_array('get_office_hours', $view->completed(), true)) {
    throw new RuntimeException('The HTTP capability did not run.');
}
echo 'User request ms: ' . round((hrtime(true) - $started) / 1e6)
    . PHP_EOL;
echo 'HTTP MCP verified.' . PHP_EOL;
