# Tokens, cost and latency: measuring our own runs

Source code of the lesson "Tokens, cost and latency: measuring our own runs" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Anatomy of a model call: the messages, the tool schema and the usage of our run": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/04-measure-runs.php`
- `examples/verify-price.php`
- `src/Support/Price.php`

## Run

- `php examples/04-measure-runs.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-price.php`
