# Connecting to an MCP server over HTTP

Source and verified exercise for Agentic AI in PHP with NeuronAI.

Use PHP 8.5 or later. Run `composer install`, copy `.env.example` to `.env`, and supply your own provider credentials privately for model calls. Keep `.env` out of Git.

The finished project is in `src/`, `examples/`, and any fixture folders. `stages/` contains intermediate teaching versions.

Runnable examples:

- `php examples/47-http-mcp.php`
- `php examples/exercise-http-mcp.php`
- `php examples/http-server.php`

Run `php examples/http-server.php start` before the HTTP examples. `--discover` checks the connection without inference. Run `php examples/http-server.php stop` afterwards. `examples/protected-endpoint.php` is an illustrative configuration, not a public service.

Follow `exercise.en.md` for the distinct practice task, expected checks, and worked solution.
