<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Mcp\HttpOfficeProtocol;
function rejectHttp(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['error' => $message], JSON_THROW_ON_ERROR);
    exit;
}
$base = 'http://127.0.0.1:48392';
$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
if ($origin !== null && !in_array($origin,
    [$base, 'http://localhost:48392'], true)) {
    rejectHttp(403, 'Untrusted Origin.');
}
if (!in_array($_SERVER['HTTP_HOST'] ?? '',
    ['127.0.0.1:48392', 'localhost:48392'], true)) {
    rejectHttp(403, 'Untrusted Host.');
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$allowed = ['/mcp', '/mcp-events', '/mcp-bad-schema'];
if (!in_array($path, $allowed, true)) {
    rejectHttp(404, 'No MCP endpoint at this path.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    rejectHttp(405, 'This fixture offers no standalone GET event stream.');
}
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if (!str_contains($accept, 'application/json')
    || !str_contains($accept, 'text/event-stream')) {
    rejectHttp(406, 'Accept both JSON and event-stream responses.');
}
try {
    $request = json_decode(file_get_contents('php://input'), true,
        flags: JSON_THROW_ON_ERROR);
} catch (JsonException) {
    rejectHttp(400, 'Malformed JSON.');
}
if (!is_array($request) || ($request['jsonrpc'] ?? '') !== '2.0'
    || !is_string($request['method'] ?? null)) {
    rejectHttp(400, 'Expected a JSON-RPC protocol message.');
}
if (isset($request['params']) && !is_array($request['params'])) {
    rejectHttp(400, 'Protocol parameters must be an object.');
}
if (isset($request['params']['arguments'])
    && !is_array($request['params']['arguments'])) {
    rejectHttp(400, 'Tool arguments must be an object.');
}
$storage = __DIR__ . '/../storage/http-office';
if (!is_dir($storage)) {
    mkdir($storage, 0700, true);
}
$method = $request['method'];
$session = $_SERVER['HTTP_MCP_SESSION_ID'] ?? '';
if ($method === 'initialize') {
    $session = bin2hex(random_bytes(16));
    file_put_contents($storage . '/' . $session . '.json',
        json_encode(['ready' => false], JSON_THROW_ON_ERROR));
    header('Mcp-Session-Id: ' . $session);
} else {
    if (!preg_match('/^[a-f0-9]{32}$/', $session)) {
        rejectHttp(400, 'A session from initialization is required.');
    }
    $sessionPath = $storage . '/' . $session . '.json';
    if (!is_file($sessionPath)) {
        rejectHttp(404, 'Session expired.');
    }
    if (($_SERVER['HTTP_MCP_PROTOCOL_VERSION'] ?? '') !== '2025-11-25') {
        rejectHttp(400, 'Negotiated protocol version header is required.');
    }
    $state = json_decode(file_get_contents($sessionPath), true,
        flags: JSON_THROW_ON_ERROR);
    if ($method === 'notifications/initialized') {
        file_put_contents($sessionPath,
            json_encode(['ready' => true], JSON_THROW_ON_ERROR));
    } elseif (!($state['ready'] ?? false)) {
        rejectHttp(400, 'Complete initialization before tool work.');
    }
}
file_put_contents($storage . '/trace.ndjson', json_encode([
    'path' => $path, 'method' => $method, 'session' => $session,
    'protocol' => $_SERVER['HTTP_MCP_PROTOCOL_VERSION'] ?? null,
], JSON_THROW_ON_ERROR) . PHP_EOL, FILE_APPEND | LOCK_EX);
if (!array_key_exists('id', $request)) {
    http_response_code(202);
    exit;
}
$response = HttpOfficeProtocol::response($request, $path === '/mcp-bad-schema');
if ($path === '/mcp-events') {
    header('Content-Type: text/event-stream');
    echo ": Controlled Streamable HTTP response\n\n";
    echo 'data: ' . json_encode(['jsonrpc' => '2.0',
        'method' => 'notifications/message', 'params' => [
            'level' => 'info', 'data' => 'Controlled fixture progress',
        ]], JSON_THROW_ON_ERROR) . "\n\n";
    echo "event: message\n";
    foreach (explode("\n", json_encode($response,
        JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)) as $line) {
        echo 'data: ' . $line . "\n";
    }
    echo "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode($response, JSON_THROW_ON_ERROR);
}
