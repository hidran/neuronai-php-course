# Streaming from the command line

Source code of the lesson "Streaming from the command line" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Streaming and its chunks": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/40-streaming-cli.php`
- `examples/exercise-streaming-cli.php`
- `exercises/07-02-streaming-cli.md`

## Run

- `php examples/40-streaming-cli.php`
- `php examples/exercise-streaming-cli.php`

## Exercise

See `exercises/07-02-streaming-cli.md`.
