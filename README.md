# Requesting structured output

Source code of the lesson "Requesting structured output" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Defining the output class": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/34-request-structured.php`
- `examples/exercise-request-structured.php`
- `examples/verify-extractor.php`
- `exercises/06-03-requesting-structured-output.md`
- `src/Agents/PersonExtractor.php`

## Run

- `php examples/34-request-structured.php`
- `php examples/exercise-request-structured.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-extractor.php`

## Exercise

See `exercises/06-03-requesting-structured-output.md`.
