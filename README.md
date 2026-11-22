# Streaming and its chunks

Source code of the lesson "Streaming and its chunks" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Lab: extracting orders from emails, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/39-stream-chunks.php`
- `examples/exercise-stream-boundaries.php`
- `examples/verify-stream.php`
- `exercises/07-01-stream-chunks.md`
- `src/Agents/StreamingAgent.php`

## Run

- `php examples/39-stream-chunks.php`
- `php examples/exercise-stream-boundaries.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-stream.php`

## Exercise

See `exercises/07-01-stream-chunks.md`.
