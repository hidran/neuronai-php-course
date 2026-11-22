# See our first agent in Inspector: the ingestion key in .env, the Inspector setup from the NeuronAI docs, and the first trace in the dashboard

Source code of the lesson "See our first agent in Inspector: the ingestion key in .env, the Inspector setup from the NeuronAI docs, and the first trace in the dashboard" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "An Inspector account: the free tier, a project and its ingestion key": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/01b-hello-inspector.php`
- `examples/verify-inspector.php`
- `exercises/01-06b-first-trace-in-inspector.md`
- `src/Config/MonitoringSettings.php`

## Run

- `php examples/01b-hello-inspector.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-inspector.php`

## Exercise

See `exercises/01-06b-first-trace-in-inspector.md`.
