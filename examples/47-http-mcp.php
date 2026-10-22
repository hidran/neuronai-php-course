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
use NeuronAI\MCP\McpException;
$started = hrtime(true);
try {
    HttpOfficeConnection::discover();
} catch (McpException | ToolException $error) {
    $category = $error instanceof McpException ? 'transport' : 'schema';
    fwrite(STDERR, 'Integration category: ' . $category . PHP_EOL);
    echo 'Office hours service unavailable.' . PHP_EOL;
    exit(2);
}
if (($argv[1] ?? '') === '--discover') {
    exit;
}
Dotenv::createImmutable(dirname(__DIR__))->load();
$view = new StreamProgress(diagnostic: true);
$stream = HttpOfficeHoursAgent::make()->setThreadId('http-office-demo')
    ->stream(new UserMessage('When is the north office open?'));
$state = ToolStreamConsumer::consume($stream, $view);
if ($state === null) {
    exit(1);
}
if (!in_array('get_office_hours', $view->completed(), true)) {
    throw new RuntimeException('The HTTP capability did not run.');
}
$reply = $state->getMessage()?->getContent() ?? '';
if (!str_contains($reply, '09:00') || !str_contains($reply, '17:00')
    || !str_contains($reply, 'Europe/Rome')) {
    throw new RuntimeException('The answer missed HTTP server facts.');
}
echo 'User request ms: ' . round((hrtime(true) - $started) / 1e6) . PHP_EOL;
echo 'HTTP MCP ' . 'verified.' . PHP_EOL;
