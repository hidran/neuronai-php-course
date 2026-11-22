# A Laravel 13 project in PhpStorm, and installing the SDK

Source code of the lesson "A Laravel 13 project in PhpStorm, and installing the SDK" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson; the next lessons build on it, one commit per lesson.

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

- `.editorconfig`
- `.env.example`
- `.gitattributes`
- `.gitignore`
- `.npmrc`
- `AGENTS.md`
- `CLAUDE.md`
- `app/Http/Controllers/Controller.php`
- `app/Models/User.php`
- `app/Providers/AppServiceProvider.php`
- `artisan`
- `bootstrap/app.php`
- `bootstrap/cache/.gitignore`
- `bootstrap/providers.php`
- `checks/verify-laravel-sdk.php`
- `composer.json`
- `config/app.php`
- `config/auth.php`
- `config/cache.php`
- `config/database.php`
- `config/filesystems.php`
- `config/logging.php`
- `config/mail.php`
- `config/queue.php`
- `config/services.php`
- `config/session.php`
- `database/.gitignore`
- `database/factories/UserFactory.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/0001_01_01_000001_create_cache_table.php`
- `database/migrations/0001_01_01_000002_create_jobs_table.php`
- `database/seeders/DatabaseSeeder.php`
- `exercises/13-01-laravel-project-sdk.md`
- `package.json`
- `phpunit.xml`
- `public/.htaccess`
- `public/favicon.ico`
- `public/index.php`
- `public/robots.txt`
- `requests/health.http`
- `resources/css/app.css`
- `resources/js/app.js`
- `resources/views/welcome.blade.php`
- `routes/console.php`
- `routes/web.php`
- `storage/app/.gitignore`
- `storage/app/private/.gitignore`
- `storage/app/public/.gitignore`
- `storage/framework/.gitignore`
- `storage/framework/cache/.gitignore`
- `storage/framework/cache/data/.gitignore`
- `storage/framework/cache/locks/.gitignore`
- `storage/framework/sessions/.gitignore`
- `storage/framework/testing/.gitignore`
- `storage/framework/views/.gitignore`
- `storage/logs/.gitignore`
- `tests/Feature/ExampleTest.php`
- `tests/TestCase.php`
- `tests/Unit/ExampleTest.php`
- `vite.config.js`

## Offline check

No key needed: the check uses NeuronAI's fake provider and prints a final OK line.

- `php checks/verify-laravel-sdk.php`

## Exercise

See `exercises/13-01-laravel-project-sdk.md`.
