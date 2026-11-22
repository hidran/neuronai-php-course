# Closing: what we built

Source code of the lesson "Closing: what we built" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Composer audit, the structured report and streaming progress": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `exercises/15-04-closing-what-we-built.md`
- `exercises/exercise-gate.php`
- `exercises/exercise-license-tool.php`

## Run

- `php exercises/exercise-gate.php`
- `php exercises/exercise-license-tool.php`

## Exercise

See `exercises/15-04-closing-what-we-built.md`.
