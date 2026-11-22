# Tools as classes, with dependencies

Source code of the lesson "Tools as classes, with dependencies" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "What the model sees: name, description and JSON schema of our tool": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/20-tool-class.php`
- `examples/database.php`
- `examples/verify-count-tool.php`
- `src/Tools/CountMessagesTool.php`

## Run

- `php examples/20-tool-class.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-count-tool.php`
