<?php
declare(strict_types=1);
// Reviewed teaching fixture. Protocol messages only on stdout.
stream_set_write_buffer(STDOUT, 0);
function reply(mixed $id, array|object $result): void
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
function definition(): array
{
    return [
        'name' => 'get_office_hours',
        'description' => 'Read the fixed office hours for north or south.',
        'inputSchema' => [
            'type' => 'object',
            'properties' => ['location' => [
                'type' => 'string',
                'description' => 'The office location: north or south.',
            ]],
            'required' => ['location'],
            'additionalProperties' => false,
        ],
        'annotations' => [
            'readOnlyHint' => true,
            'destructiveHint' => false,
            'idempotentHint' => true,
            'openWorldHint' => false,
        ],
    ];
}
function officeHours(array $arguments): array
{
    $location = $arguments['location'] ?? null;
    if (!is_string($location) || array_keys($arguments) !== ['location']) {
        return ['content' => [['type' => 'text',
            'text' => 'A location string is required.']], 'isError' => true];
    }
    $table = ['north' => '09:00-17:00', 'south' => '10:00-18:00'];
    if (!isset($table[$location])) {
        return ['content' => [['type' => 'text',
            'text' => 'Unknown office location.']], 'isError' => true];
    }
    $text = json_encode(['location' => $location,
        'hours' => $table[$location], 'timezone' => 'Europe/Rome'],
        JSON_THROW_ON_ERROR);
    return ['content' => [['type' => 'text', 'text' => $text]],
        'isError' => false];
}
$initialized = false;
$ready = false;
fwrite(STDERR, "Office hours fixture ready on stdio.\n");
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
    $id = $request['id'] ?? null;
    $method = $request['method'];
    if (!array_key_exists('id', $request)) {
        if ($method === 'notifications/initialized' && $initialized) {
            $ready = true;
        }
        continue;
    }
    if ($method === 'initialize') {
        $initialized = true;
        reply($id, [
            'protocolVersion' => '2025-11-25',
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => 'office-hours-fixture',
                'version' => '1.0.0'],
        ]);
        continue;
    }
    if ($method === 'ping') {
        reply($id, new stdClass());
        continue;
    }
    if (!$ready) {
        reject($id, -32002, 'Server is not initialized');
        continue;
    }
    if ($method === 'tools/list') {
        reply($id, ['tools' => [definition()]]);
        continue;
    }
    if ($method === 'tools/call') {
        $params = $request['params'] ?? [];
        if (($params['name'] ?? '') !== 'get_office_hours') {
            reject($id, -32602, 'Unknown tool');
            continue;
        }
        $arguments = $params['arguments'] ?? [];
        if (!is_array($arguments)) {
            reject($id, -32602, 'Invalid tool arguments');
            continue;
        }
        reply($id, officeHours($arguments));
        continue;
    }
    reject($id, -32601, 'Method not found');
}
