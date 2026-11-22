# Toolkits

Source code of the lesson "Toolkits" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Structured tool input with #[SchemaProperty]": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `docker/php/Dockerfile`
- `examples/24-toolkits.php`
- `src/Tools/ProjectToolkit.php`

## Run

- `php examples/24-toolkits.php`
