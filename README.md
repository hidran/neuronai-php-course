# Lab: weather and calculator agent

Source code of the lesson "Lab: weather and calculator agent" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Provider tools: native web search, when it pays off": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/30-weather-agent.php`
- `examples/verify-weather.php`
- `src/Agents/WeatherAgent.php`
- `src/Tools/WeatherTool.php`

## Run

- `php examples/30-weather-agent.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-weather.php`
