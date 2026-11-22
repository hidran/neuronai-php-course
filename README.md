# Reading an agent trace, part 1

Source code of the lesson "Reading an agent trace, part 1" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Observe agent runs with Inspector, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/50-reading-traces.php`
- `examples/exercise-reading-traces.php`
- `examples/verify-traces.php`
- `src/Observability/Prices.php`
- `src/Observability/TraceRecorder.php`
- `src/Observability/TraceReport.php`

## Run

- `php examples/50-reading-traces.php`
- `php examples/exercise-reading-traces.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-traces.php`
