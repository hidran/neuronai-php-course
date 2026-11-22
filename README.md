# Sending a message from the page: the script, the two guards and the answer

Source code of the lesson "Sending a message from the page: the script, the two guards and the answer" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "A support chat in the browser: the login, the conversation list and the stored transcript": compare it with the parent commit to see the lesson's work.

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

- `exercises/13-12-chat-from-the-page.md`
- `exercises/exercise-last-line-check.php`
- `exercises/exercise-message-limit-check.php`
- `resources/views/conversations/show.blade.php`
- `resources/views/layouts/app.blade.php`
- `routes/web.php`

## Run

- `php exercises/exercise-last-line-check.php`
- `php exercises/exercise-message-limit-check.php`

## Exercise

See `exercises/13-12-chat-from-the-page.md`.
