# Defining the output class

Source code of the lesson "Defining the output class" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Why structured output exists: parsing free text breaks": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/33-output-class.php`
- `examples/verify-person.php`
- `exercises/06-02-output-class.md`
- `src/Output/Person.php`

## Run

- `php examples/33-output-class.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-person.php`

## Exercise

See `exercises/06-02-output-class.md`.
