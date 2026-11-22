# A deterministic test suite with fake providers and tools

Source code of the lesson "A deterministic test suite with fake providers and tools" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Evaluating non deterministic answers, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `bin/mutation-check.php`
- `examples/52-deterministic-suite.php`
- `examples/mutation-check.php`
- `exercises/ExerciseTest.php`
- `exercises/InvoiceTest.php`
- `exercises/OrchestrationTest.php`
- `exercises/RecoveryTest.php`
- `exercises/VisibilityTest.php`
- `exercises/WeatherTest.php`
- `phpunit.xml`
- `src/Deterministic/HttpWeatherClient.php`
- `src/Deterministic/Invoice.php`
- `src/Deterministic/InvoiceConsistency.php`
- `src/Deterministic/InvoiceLine.php`
- `src/Deterministic/RecoveryAgent.php`
- `src/Deterministic/SensitiveService.php`
- `src/Deterministic/SensitiveTool.php`
- `src/Deterministic/StubWeatherClient.php`
- `src/Deterministic/ToolErrorPolicy.php`
- `src/Deterministic/WeatherClient.php`
- `src/Deterministic/WeatherTool.php`
- `tests/ExerciseTest.php`
- `tests/InvoiceTest.php`
- `tests/OrchestrationTest.php`
- `tests/RecoveryTest.php`
- `tests/VisibilityTest.php`
- `tests/WeatherTest.php`
- `tests/bootstrap.php`

## Run

- `php examples/52-deterministic-suite.php`
