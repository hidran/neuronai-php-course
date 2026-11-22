# Provider swap: the same agent on Anthropic, OpenAI, Gemini and Ollama

Source code of the lesson "Provider swap: the same agent on Anthropic, OpenAI, Gemini and Ollama" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Deferred instructions with context()": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/13-provider-swap.php`
- `examples/13b-fake-provider.php`

## Run

- `php examples/13-provider-swap.php`
- `php examples/13b-fake-provider.php`
