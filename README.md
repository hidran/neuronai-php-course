# Nested objects and typed arrays

Source code of the lesson "Nested objects and typed arrays" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Requesting structured output": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/35-nested-objects.php`
- `examples/exercise-nested-objects.php`
- `examples/verify-nested.php`
- `exercises/06-04-nested-objects.md`
- `src/Output/Address.php`
- `src/Output/Delivery.php`
- `src/Output/Tag.php`

## Run

- `php examples/35-nested-objects.php`
- `php examples/exercise-nested-objects.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-nested.php`

## Exercise

See `exercises/06-04-nested-objects.md`.
