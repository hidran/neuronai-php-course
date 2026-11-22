# Structured output vs tool calling

Source code of the lesson "Structured output vs tool calling" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Validation and retry": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/37-output-vs-tools.php`
- `examples/exercise-output-vs-tools.php`
- `exercises/06-06-structured-output-vs-tools.md`
- `src/Output/PackageReport.php`

## Run

- `php examples/37-output-vs-tools.php`
- `php examples/exercise-output-vs-tools.php`

## Exercise

See `exercises/06-06-structured-output-vs-tools.md`.
