# Connect to an MCP server over HTTP

Source code of the lesson "Connect to an MCP server over HTTP" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Connect a local tool server with MCP, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/47-http-mcp.php`
- `examples/exercise-http-mcp.php`
- `examples/http-server.php`
- `examples/protected-endpoint.php`
- `examples/verify-http-mcp.php`
- `requests/initialize-office.http`
- `servers/http-office-router.php`
- `src/Config/McpSettings.php`
- `src/Mcp/HttpOfficeProtocol.php`
- `src/Mcp/OfficeEndpoints.php`

## Run

- `php examples/47-http-mcp.php`
- `php examples/exercise-http-mcp.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-http-mcp.php`
