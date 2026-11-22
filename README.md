# Our first tool: watching the agent loop happen

Source code of the lesson "Our first tool: watching the agent loop happen" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "The autonomy ladder: our hello world is rung 1, who decides what happens next": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/02-first-tool.php`
- `examples/verify-first-tool.php`
- `src/HelloAgent.php`
- `src/PackageVersionTool.php`

## Run

- `php examples/02-first-tool.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-first-tool.php`
