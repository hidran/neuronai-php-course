# Tool error handling: returning the error to the model

Source code of the lesson "Tool error handling: returning the error to the model" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Visibility: conditional tool availability": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/28-tool-errors.php`
- `examples/verify-tool-errors.php`

## Run

- `php examples/28-tool-errors.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-tool-errors.php`
