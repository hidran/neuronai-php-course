# Message stores and the thread ID, part 2

Source code of the lesson "Message stores and the thread ID, part 2" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Message stores and the thread ID": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/16-message-stores.php`
- `exercises/04-03b-message-stores.md`

## Run

- `php examples/16-message-stores.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-stores.php`

## Exercise

See `exercises/04-03b-message-stores.md`.
