# SystemPrompt: background, steps, output

Source code of the lesson "SystemPrompt: background, steps, output" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "The framework CLI and the agent class: extend or compose": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/11-show-prompt.php`
- `src/Agents/AssistantAgent.php`

## Run

- `php examples/11-show-prompt.php`
