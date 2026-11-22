# A support chat in the browser: the login, the conversation list and the stored transcript

Source code of the lesson "A support chat in the browser: the login, the conversation list and the stored transcript" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "EloquentMessageStore: the migration and model we write ourselves": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Put your own provider key in `.env` (never commit it). The tests run without any key: `php artisan test`.

## What this lesson changes

- `app/Http/Controllers/ConversationController.php`
- `app/Http/Controllers/LoginController.php`
- `app/Support/Transcript.php`
- `app/Support/TranscriptLine.php`
- `database/seeders/DatabaseSeeder.php`
- `database/seeders/DemoSeeder.php`
- `exercises/13-11-support-chat-page.md`
- `exercises/exercise-last-line-check.php`
- `resources/views/conversations/index.blade.php`
- `resources/views/conversations/show.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/login.blade.php`
- `routes/web.php`
- `tests/Feature/ExampleTest.php`

## Run

- `php exercises/exercise-last-line-check.php`

## Exercise

See `exercises/13-11-support-chat-page.md`.
