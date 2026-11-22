# Evals: PHPUnit for non-deterministic systems

Source code of the lesson "Evals: PHPUnit for non-deterministic systems" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Reading an agent trace, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `evaluation.php`
- `evaluators/Suite/TeachingEvaluator.php`
- `evaluators/Suite/WeatherEvaluator.php`
- `evaluators/TeachingCases.php`
- `examples/51-evaluation.php`
- `examples/exercise-evaluation.php`
- `examples/verify-evaluation.php`
- `src/Agents/TeachingAgent.php`

## Run

- `php examples/51-evaluation.php`
- `php examples/exercise-evaluation.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-evaluation.php`
