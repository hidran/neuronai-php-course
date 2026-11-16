# A support chat in the browser: the login, the conversation list and the stored transcript

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

## The Neuron facade and NeuronAI 4

With `neuron-core/neuron-laravel` 2.0.2 every call through the `Neuron` facade (`Neuron::chat()`, `stream()`, `structured()`) stops with "This agent has no thread ID: bind one with setThreadId() first." The agents of the course are classes resolved from the container and are not affected: only code that calls the facade directly is.

A fix is proposed to the package. If your installed version still shows the error and you want to use the facade, install the package from the instructor's fork, which contains the fix:

```bash
composer config repositories.neuron-laravel vcs https://github.com/hidran/neuron-laravel
composer require "neuron-core/neuron-laravel:dev-fix/facade-thread-id as 2.0.2"
```

When an official release includes the fix, go back to it with `composer config --unset repositories.neuron-laravel` and `composer require "neuron-core/neuron-laravel:^2.0"`.

`files/` holds the lesson's runnable checks and exercise references, run against the project folder:

- `php files/exercise-last-line-check.php .`
- `php files/verify-support-chat-page.php .`

Follow `exercise.en.md` for the practice task, its expected checks and the worked solution.
