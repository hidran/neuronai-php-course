<?php
declare(strict_types=1);
// Configuration shape only: this endpoint is never contacted.
use App\Config\McpSettings;
$mcp = McpSettings::fromEnv($_ENV);
return [
    'url' => $mcp->url,
    'token' => $mcp->token,
    'headers' => ['X-Client' => 'office-hours-example'],
    'timeout' => 3,
];
