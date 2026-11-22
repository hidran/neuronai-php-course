# Anatomy of a model call: the messages, the tool schema and the usage of our run

Source code of the lesson "Anatomy of a model call: the messages, the tool schema and the usage of our run" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Our first tool: watching the agent loop happen": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/03-anatomy.php`

## Run

- `php examples/03-anatomy.php`
