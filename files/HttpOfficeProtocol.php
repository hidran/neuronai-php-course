<?php
declare(strict_types=1);
namespace App\Mcp;
final class HttpOfficeProtocol
{
    public static function definition(bool $badSchema = false): array
    {
        $property = ['type' => 'string',
            'description' => 'The fixture location: north or south.'];
        if ($badSchema) {
            $property = ['anyOf' => [['type' => 'string'], ['type' => 'null']]];
        }
        return [
            'name' => 'get_office_hours',
            'description' => 'Read fixed office hours for north or south.',
            'inputSchema' => ['type' => 'object',
                'properties' => ['location' => $property],
                'required' => ['location']],
            'annotations' => ['readOnlyHint' => true,
                'destructiveHint' => false, 'idempotentHint' => true,
                'openWorldHint' => false],
        ];
    }
    public static function call(array $arguments): array
    {
        $location = $arguments['location'] ?? null;
        $hours = ['north' => '09:00-17:00', 'south' => '10:00-18:00'];
        if (!is_string($location) || !isset($hours[$location])
            || array_keys($arguments) !== ['location']) {
            return ['isError' => true, 'content' => [[
                'type' => 'text', 'text' => 'Unknown or invalid location.',
            ]]];
        }
        return ['isError' => false, 'content' => [[
            'type' => 'text',
            'text' => json_encode(['location' => $location,
                'hours' => $hours[$location], 'timezone' => 'Europe/Rome'],
                JSON_THROW_ON_ERROR),
        ]]];
    }
    public static function response(array $request, bool $bad): array
    {
        $id = $request['id'];
        $params = $request['params'] ?? [];
        $result = match ($request['method']) {
            'initialize' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => ['tools' => ['listChanged' => false],
                    'logging' => new \stdClass()],
                'serverInfo' => ['name' => 'office-hours-http-fixture',
                    'version' => '1.0.0'],
            ],
            'ping', 'logging/setLevel' => new \stdClass(),
            'tools/list' => ['tools' => [self::definition($bad)]],
            'tools/call' => ($params['name'] ?? '') === 'get_office_hours'
                ? self::call($params['arguments'] ?? []) : null,
            default => null,
        };
        if ($result === null) {
            return ['jsonrpc' => '2.0', 'id' => $id, 'error' => [
                'code' => $request['method'] === 'tools/call' ? -32602 : -32601,
                'message' => 'Unknown method or capability.',
            ]];
        }
        return ['jsonrpc' => '2.0', 'id' => $id, 'result' => $result];
    }
}
