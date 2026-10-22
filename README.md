# Source types and file identifiers

Source and verified exercise for **Agentic AI in PHP with NeuronAI**.

Use PHP 8.5 or later. Run `composer install`. Copy `.env.example` to `.env` and configure your own provider credentials for live calls. Keep private credentials out of Git.

The project layout contains the finished example. The `files/` and `stages/` folders preserve variants for comparison.

Runnable examples:

- `php examples/43-source-types.php`
- `php examples/exercise-source-references.php`

Follow `exercise.en.md` for the exercise, expected checks and worked solution.

For the HTTP client, copy `http-client.env.json.example` to `http-client.private.env.json`, set your own key privately, and select the studio environment in PhpStorm. Use the request files to upload each fixture before running its example or exercise. Create `storage/` if needed. Never commit upload responses or keys.
