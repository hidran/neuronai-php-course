# Optional: free local models with Ollama, and why they can be slow

Source code of the lesson "Optional: free local models with Ollama, and why they can be slow" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "See our first agent in Inspector: the ingestion key in .env, the Inspector setup from the NeuronAI docs, and the first trace in the dashboard": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.
