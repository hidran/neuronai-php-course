# Property types: scalars to objects, part 2

Source code of the lesson "Property types: scalars to objects, part 2" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Property types: scalars, arrays and objects": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/exercise-package-inputs.php`
- `exercises/05-04b-property-types.md`

## Run

- `php examples/exercise-package-inputs.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-check-packages.php`

## Exercise

See `exercises/05-04b-property-types.md`.
