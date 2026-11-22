# Why structured output exists: parsing free text breaks

Source code of the lesson "Why structured output exists: parsing free text breaks" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "A read-only database analyst, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/32-free-text-parsing.php`
- `exercises/06-01-why-structured-output.md`

## Run

- `php examples/32-free-text-parsing.php`

## Exercise

See `exercises/06-01-why-structured-output.md`.
