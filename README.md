# The framework CLI and the agent class: extend or compose

Source code of the lesson "The framework CLI and the agent class: extend or compose" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Agent and RAG are workflows: the nodes behind chat()": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/09-assistant.php`
- `examples/10-compose.php`
- `examples/bootstrap.php`
- `examples/verify-assistant.php`
- `src/Agents/AssistantAgent.php`

## Run

- `php examples/09-assistant.php`
- `php examples/10-compose.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-assistant.php`
