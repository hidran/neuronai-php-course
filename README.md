# The Neuron facade, and what to use instead

Source and verified exercise for Agentic AI in PHP with NeuronAI.

This branch is the Laravel 13 application as the lesson leaves it. Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
```

Supply your own provider credentials in `.env` for model calls. Credentials are never included.

`files/` holds the lesson's runnable checks and exercise references, run against the project folder:

- `php files/verify-facade-alternative.php .`

Follow `exercise.en.md` for the practice task, its expected checks and the worked solution.
