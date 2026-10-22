<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Mcp\PolicyFixture;
stream_set_write_buffer(STDOUT, 0);
$flags = array_slice($argv, 1);
$fixture = new PolicyFixture($flags);
$audit = dirname(__DIR__) . '/storage/mcp-policy.jsonl';
function respond(mixed $id, array|object $result): void
{
    echo json_encode(['jsonrpc' => '2.0', 'id' => $id,
        'result' => $result], JSON_THROW_ON_ERROR) . PHP_EOL;
    fflush(STDOUT);
}
function reject(mixed $id, int $code, string $message): void
{
    echo json_encode(['jsonrpc' => '2.0', 'id' => $id,
        'error' => ['code' => $code, 'message' => $message]],
        JSON_THROW_ON_ERROR) . PHP_EOL;
    fflush(STDOUT);
}
$initialized = $ready = false;
while (($line = fgets(STDIN)) !== false) {
    try {
        $request = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        reject(null, -32700, 'Parse error');
        continue;
    }
    if (!is_array($request) || ($request['jsonrpc'] ?? '') !== '2.0'
        || !is_string($request['method'] ?? null)) {
        reject(null, -32600, 'Invalid Request');
        continue;
    }
    $method = $request['method'];
    if (!array_key_exists('id', $request)) {
        if ($method === 'notifications/initialized' && $initialized) {
            $ready = true;
        }
        continue;
    }
    $id = $request['id'];
    if ($method === 'initialize') {
        $initialized = true;
        respond($id, ['protocolVersion' => '2025-11-25',
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => 'reviewed-policy-fixture',
                'version' => '1.0.0']]);
        continue;
    }
    if ($method === 'ping') {
        respond($id, new stdClass());
        continue;
    }
    if (!$ready) {
        reject($id, -32002, 'Server is not initialized');
        continue;
    }
    if ($method === 'tools/list') {
        respond($id, ['tools' => $fixture->definitions()]);
        continue;
    }
    if ($method === 'tools/call') {
        $params = $request['params'] ?? [];
        $name = $params['name'] ?? '';
        $arguments = $params['arguments'] ?? [];
        if (!is_string($name) || !is_array($arguments)
            || !in_array($name, array_column($fixture->definitions(), 'name'))
        ) {
            reject($id, -32602, 'Unknown tool or invalid arguments');
            continue;
        }
        file_put_contents($audit, json_encode([
            'name' => $name, 'arguments' => $arguments,
        ]) . PHP_EOL, FILE_APPEND | LOCK_EX);
        respond($id, $fixture->call($name, $arguments));
        continue;
    }
    reject($id, -32601, 'Method not found');
}
