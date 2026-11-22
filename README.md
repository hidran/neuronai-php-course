# Provider tools: native web search, when it pays off

Source code of the lesson "Provider tools: native web search, when it pays off" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Tool error handling: returning the error to the model": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/29-provider-tools.php`
- `examples/zz-check-29.php`

## Run

- `php examples/29-provider-tools.php`
