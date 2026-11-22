# A deterministic test suite with fake providers and tools, part 2

Source code of the lesson "A deterministic test suite with fake providers and tools, part 2" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "A deterministic test suite with fake providers and tools": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `composer.json`
- `exercises/10-04b-deterministic-test-suite.md`

## Exercise

See `exercises/10-04b-deterministic-test-suite.md`.
