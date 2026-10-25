<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/McpSettings.php';
require __DIR__ . '/OfficeEndpoints.php';
require __DIR__ . '/HttpOfficeProtocol.php';
use App\Config\McpSettings;
use App\Mcp\HttpOfficeProtocol;
use App\Mcp\OfficeEndpoints;
$mcp = McpSettings::fromEnv(['MCP_URL' => 'https://mcp.example.invalid',
    'MCP_TOKEN' => 'secret']);
if ($mcp->url !== 'https://mcp.example.invalid' || $mcp->token !== 'secret') {
    throw new RuntimeException('settings not read');
}
try {
    McpSettings::fromEnv(['MCP_URL' => 'https://mcp.example.invalid']);
    throw new LogicException('missing token accepted');
} catch (RuntimeException $error) {
    if ($error->getMessage() !== 'MCP_TOKEN is not set') {
        throw $error;
    }
}
$_ENV['MCP_URL'] = 'https://mcp.example.invalid/office';
$_ENV['MCP_TOKEN'] = 'secret';
ob_start();
$config = require __DIR__ . '/protected-endpoint.php';
$printed = ob_get_clean();
if ($printed !== '' || $config['token'] !== 'secret'
    || $config['url'] !== 'https://mcp.example.invalid/office') {
    throw new RuntimeException('protected endpoint config wrong');
}
$router = file_get_contents(__DIR__ . '/http-office-router.php');
$server = file_get_contents(__DIR__ . '/http-server.php');
if (!str_contains($router, OfficeEndpoints::BASE)
    || !str_contains($server, '127.0.0.1:48392')) {
    throw new RuntimeException('fixture endpoints drifted');
}
$response = HttpOfficeProtocol::response(['jsonrpc' => '2.0', 'id' => 7,
    'method' => 'tools/call', 'params' => ['name' => 'get_office_hours',
        'arguments' => ['location' => 'north']]], false);
if (!str_contains($response['result']['content'][0]['text'], '09:00-17:00')) {
    throw new RuntimeException('protocol call wrong');
}
echo 'HTTP MCP OK' . PHP_EOL;
