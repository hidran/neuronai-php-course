# The ecosystem: Inspector, Maestro live, Neuron Hub, Studio and Symfony

Source code of the lesson "The ecosystem: Inspector, Maestro live, Neuron Hub, Studio and Symfony" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Version strategy: recognizing v3 code, upgrading to 4.x, following minor releases": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `docs/ecosystem/neuronai-studio.txt`
- `examples/exercise-compatibility.php`
- `exercises/15-02-ecosystem-maestro.md`

## Run

- `php examples/exercise-compatibility.php`

## Exercise

See `exercises/15-02-ecosystem-maestro.md`.
