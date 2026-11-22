# Agent and RAG are workflows: the nodes behind chat()

Source code of the lesson "Agent and RAG are workflows: the nodes behind chat()" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "The four pillars and everything is an interface: reading the source in PhpStorm": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/08-agent-nodes.php`

## Run

- `php examples/08-agent-nodes.php`
