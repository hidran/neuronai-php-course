# Choosing the right rung: one feature as a single call and as an agent, compared

Source code of the lesson "Choosing the right rung: one feature as a single call and as an agent, compared" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Non-determinism: the same prompt five times, and what changes": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/06-two-rungs.php`

## Run

- `php examples/06-two-rungs.php`
