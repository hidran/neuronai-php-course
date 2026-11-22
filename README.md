# Connect a local tool server with MCP

Source code of the lesson "Connect a local tool server with MCP" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Building an invoice extractor, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/46-local-mcp.php`
- `examples/exercise-local-mcp.php`
- `examples/verify-local-mcp.php`
- `servers/office-hours.php`
- `src/Agents/OfficeHoursAgent.php`
- `src/Mcp/McpConnection.php`

## Run

- `php examples/46-local-mcp.php`
- `php examples/exercise-local-mcp.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-local-mcp.php`
