# Property types: scalars, arrays and objects

Source code of the lesson "Property types: scalars, arrays and objects" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Tool descriptions are prompt engineering: an A/B experiment": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/22-property-types.php`
- `examples/verify-check-packages.php`
- `src/Tools/CheckPackagesTool.php`

## Run

- `php examples/22-property-types.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-check-packages.php`
