# Retrieval quality, part 2

Source code of the lesson "Retrieval quality, part 2" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Retrieval quality": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `composer.json`
- `examples/exercise-retrieval-quality.php`
- `exercises/11-06b-retrieval-quality.md`

## Run

- `php examples/exercise-retrieval-quality.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-retrieval-quality.php`

## Exercise

See `exercises/11-06b-retrieval-quality.md`.
