<?php
declare(strict_types=1);
// Configuration shape only. This illustrative endpoint is never contacted.
return [
    'url' => 'https://mcp.example.invalid/office',
    'token' => $_ENV['MCP_TOKEN'] ?? '',
    'headers' => ['X-Client' => 'office-hours-example'],
    'timeout' => 3,
];
