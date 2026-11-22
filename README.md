# Streaming with tools

Source code of the lesson "Streaming with tools" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Streaming from the command line": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/41-streaming-tools.php`
- `examples/exercise-tool-progress.php`
- `examples/verify-streaming-tools.php`
- `src/Agents/ServerAgent.php`
- `src/Streaming/StreamProgress.php`
- `src/Streaming/ToolStreamConsumer.php`
- `src/Tools/ServerConfigurationTool.php`

## Run

- `php examples/41-streaming-tools.php`
- `php examples/exercise-tool-progress.php`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php examples/verify-streaming-tools.php`
