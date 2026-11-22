# Deferred instructions with context()

Source code of the lesson "Deferred instructions with context()" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "SystemPrompt: background, steps, output": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/12-turn-context.php`
- `examples/verify-context.php`
- `src/Agents/AssistantAgent.php`

## Run

- `php examples/12-turn-context.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-context.php`
