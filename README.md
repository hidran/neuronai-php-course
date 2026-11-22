# Testing the chat app without calling a model

Source code of the lesson "Testing the chat app without calling a model" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Sending a message from the page: the script, the two guards and the answer": compare it with the parent commit to see the lesson's work.

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

- `exercises/13-13-testing-the-chat-app.md`
- `exercises/SupportChatPageExerciseTest.php`
- `exercises/exercise-message-limit-check.php`
- `tests/Feature/SupportChatPageTest.php`

## Run

- `php exercises/exercise-message-limit-check.php`

## Exercise

See `exercises/13-13-testing-the-chat-app.md`.
