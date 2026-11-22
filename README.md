# Observe agent runs with Inspector

Source code of the lesson "Observe agent runs with Inspector" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Filter and trust MCP tools, part 2": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/49-observability.php`
- `examples/exercise-observability.php`
- `examples/verify-observability.php`
- `src/Agents/ObservedWeatherAgent.php`
- `src/Config/MonitoringSettings.php`
- `src/Observability/AgentMonitor.php`
- `src/Observability/EventFileLogger.php`
- `src/Tools/FixtureWeatherTool.php`

## Run

- `php examples/49-observability.php`
- `php examples/exercise-observability.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-observability.php`
