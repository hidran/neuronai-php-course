# Exercise: a safe status for embeddings too

Start from the Laravel project at the end of the lesson: `config/neuron.php` published, the NeuronAI block in `.env.example`, and the `ai:status` command in `routes/console.php`.

1. Add a second closure command, `ai:embeddings`, next to `ai:status`. It reads `config('neuron.embedding.default')`; when the value is empty it fails with `Set NEURON_EMBEDDING_PROVIDER first.`; otherwise it prints `Embeddings: <driver>, model: <model>` with the model read from `neuron.embedding.<driver>.model`. It never prints a key and never dumps an array.
2. Add the placeholder `NEURON_EMBEDDING_PROVIDER=` to `.env.example`, with a comment that the embedding model and its dimensions must match the index.
3. Run `php artisan ai:embeddings` with the variable missing, then with `NEURON_EMBEDDING_PROVIDER=openai php artisan ai:embeddings` (the shell variable wins over `.env`).
4. Build the cache with `NEURON_EMBEDDING_PROVIDER=openai php artisan config:cache`, then run `NEURON_EMBEDDING_PROVIDER=ollama php artisan ai:embeddings`. Run `php artisan config:clear` and the same command once more. Explain the two different results.

Acceptance: the command fails with the message when the setting is missing; `Embeddings: openai, model: text-embedding-ada-002` with the shell variable; with a cached configuration the shell variable no longer changes the result, after `config:clear` it does. `.env.example` holds no value for any key.
