# Filter and trust MCP tools

Source code of the lesson "Filter and trust MCP tools" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Connect to an MCP server over HTTP, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/48-mcp-policy.php`
- `examples/exercise-mcp-policy.php`
- `examples/verify-mcp-policy.php`
- `servers/policy-tools.php`
- `src/Agents/PolicyAgent.php`
- `src/Mcp/FramedMcpTool.php`
- `src/Mcp/McpPolicy.php`
- `src/Mcp/PolicyFixture.php`

## Run

- `php examples/48-mcp-policy.php`
- `php examples/exercise-mcp-policy.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-mcp-policy.php`
