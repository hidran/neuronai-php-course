# Filtering and trusting MCP tools

Source and verified exercise for Agentic AI in PHP with NeuronAI.

Use PHP 8.5 or later. Run `composer install`, copy `.env.example` to `.env`, and supply your own provider credentials privately for model calls. Keep `.env` out of Git.

The finished project is in `src/`, `examples/`, and any fixture folders. `stages/` contains intermediate teaching versions.

Runnable examples:

- `php examples/48-mcp-policy.php`
- `php examples/exercise-mcp-policy.php`

Run `php examples/48-mcp-policy.php --compare`, `--compatibility`, `--permissions`, `--metadata`, `--approval`, or `--collisions` for local policy checks. Only `--invoke` and the exercise need provider credentials. The maintenance fixture never performs real maintenance.

Follow `exercise.en.md` for the distinct practice task, expected checks, and worked solution.
