# Observe agent runs with Inspector

Source and verified exercise for Agentic AI in PHP with NeuronAI.

Use PHP 8.5 or later and run `composer install`. Copy `.env.example` to `.env` and privately configure your own Anthropic key and model. Keep credentials out of Git.

The weather tool returns fixed teaching temperatures, not live weather. Local event delivery and backend ingestion are separate checks.

Run the finished examples:

- `php examples/49-observability.php local`: broad event listener.
- `php examples/49-observability.php tools`: actual completed tool names and arguments.
- `php examples/49-observability.php log`: name-only application event log.
- `php examples/49-observability.php inspector`: real Inspector subscriber.
- `php examples/49-observability.php factory`: monitored factory-returned instance.
- `php examples/exercise-observability.php`: distinct instance-isolation exercise.

The two backend modes additionally require your own `INSPECTOR_INGESTION_KEY`; `INSPECTOR_API` is accepted as an alternative when the unused `INSPECTOR_INGESTION_KEY` entry is removed. Sign in to your own Inspector application, then match the actual thread label and hash saved in `storage/inspector-latest.json` with the corresponding trace. An answer alone does not prove delivery. SDK compatibility is checked against `Inspector\Neuron\V4\InspectorSubscriber`.

The `src/` and `examples/` folders form the finished project. `files/` and `stages/` preserve teaching variants. Follow `exercise.en.md` for acceptance criteria, including the separate backend check. Instructor browser automation and private configuration are excluded.
