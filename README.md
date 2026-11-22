# Tool descriptions are prompt engineering: an A/B experiment

Source code of the lesson "Tool descriptions are prompt engineering: an A/B experiment" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Tools as classes, with dependencies, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/21-description-ab.php`

## Run

- `php examples/21-description-ab.php`
