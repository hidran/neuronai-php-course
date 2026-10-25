<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OfficeHoursAgent;
use App\Config\ProviderFactory;
use App\Mcp\McpConnection;
use App\Streaming\StreamProgress;
use App\Streaming\ToolStreamConsumer;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$root = dirname(__DIR__);
$started = hrtime(true);
$connection = McpConnection::local($root . '/servers/office-hours.php')
    ->only(['get_office_hours']);
foreach ($connection->tools() as $tool) {
    $schema = $tool->getInputSchema();
    echo 'Discovered: ' . $tool->getName() . PHP_EOL;
    echo 'Input: ' . implode(', ', array_keys($schema['properties']))
        . ' (required: ' . implode(', ', $schema['required']) . ')'
        . PHP_EOL;
    echo 'Tool class: ' . $tool::class . PHP_EOL;
}
echo 'Discovery ms: ' . round((hrtime(true) - $started) / 1e6)
    . PHP_EOL;
if (($argv[1] ?? '') === '--discover') {
    echo 'Discovery check complete.' . PHP_EOL;
    exit;
}
$provider = new ProviderFactory($settings)->create();
$agent = OfficeHoursAgent::make($provider, $connection->connector())
    ->setThreadId('office-demo');
$view = new StreamProgress(diagnostic: true);
$stream = $agent->stream(
    new UserMessage('When is the north office open?')
);
$state = new ToolStreamConsumer($view)->consume($stream);
if ($state === null) {
    exit(1);
}
if (!in_array('get_office_hours', $view->completed(), true)) {
    throw new RuntimeException('The server capability did not run.');
}
echo 'Local MCP verified.' . PHP_EOL;
