<?php
declare(strict_types=1);
namespace App\Mcp;
final class OfficeEndpoints
{
    public const string BASE = 'http://127.0.0.1:48392';
    public const string MCP = self::BASE . '/mcp';
    public const string EVENTS = self::BASE . '/mcp-events';
    public const string BAD_SCHEMA = self::BASE . '/mcp-bad-schema';
    public const string NOT_PROTOCOL = self::BASE . '/not-an-endpoint';
}
