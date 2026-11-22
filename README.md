# Measuring multimodal cost

Source code of the lesson "Measuring multimodal cost" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Source types and file identifiers": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/44-multimodal-cost.php`
- `examples/exercise-multimodal-cost.php`
- `exercises/08-03-multimodal-cost-design.md`
- `fixtures/multimodal-cost/build-fixtures.py`
- `fixtures/multimodal-cost/full.png`
- `fixtures/multimodal-cost/manifest.json`
- `fixtures/multimodal-cost/medium.png`
- `fixtures/multimodal-cost/tiny.png`
- `src/Media/BenchmarkReport.php`
- `src/Media/BenchmarkRow.php`
- `src/Media/BenchmarkRunner.php`
- `src/Media/CostProbe.php`
- `src/Media/FixtureSet.php`
- `src/Media/ImageCase.php`
- `src/Media/Measurement.php`

## Run

- `php examples/44-multimodal-cost.php`
- `php examples/exercise-multimodal-cost.php`

## Exercise

See `exercises/08-03-multimodal-cost-design.md`.
