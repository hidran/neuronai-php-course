<?php

declare(strict_types=1);

// Checks that the environment is ready for the course, with or without Docker.
// It prints whether each key is set, never its value.

require __DIR__ . '/../vendor/autoload.php';

use Composer\InstalledVersions;
use Dotenv\Dotenv;

Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

function env(string $key, ?string $default = null): ?string
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    return ($value === false || $value === '') ? $default : $value;
}

function check(string $label, bool $ok, string $detail = ''): void
{
    printf("%-28s %s %s\n", $label, $ok ? 'ok ' : 'NO ', $detail);
}

check('PHP 8.5 or later', version_compare(PHP_VERSION, '8.5.0', '>='), PHP_VERSION);

foreach (['curl', 'mbstring', 'pdo_mysql', 'pcntl', 'posix'] as $extension) {
    check("extension $extension", extension_loaded($extension));
}

check('neuron-core/neuron-ai', InstalledVersions::isInstalled('neuron-core/neuron-ai'),
    InstalledVersions::getPrettyVersion('neuron-core/neuron-ai') ?? '');

check('provider', true, env('NEURON_PROVIDER', 'ollama'));
check('ANTHROPIC_KEY set', env('ANTHROPIC_KEY') !== null);
check('OPENAI_KEY set', env('OPENAI_KEY') !== null);
check('GEMINI_KEY set', env('GEMINI_KEY') !== null);

$ollama = rtrim(env('OLLAMA_URL', 'http://localhost:11434/api'), '/') . '/tags';
$context = stream_context_create(['http' => ['timeout' => 2]]);
check('Ollama reachable', @file_get_contents($ollama, false, $context) !== false, '(optional)');

try {
    new PDO(
        env('RAG_DSN', 'mysql:host=127.0.0.1;port=3306;dbname=neuron'),
        env('DB_USERNAME', 'root'),
        env('DB_ROOT_PASSWORD', 'secret'),
        [PDO::ATTR_TIMEOUT => 2],
    );
    check('MariaDB reachable', true, '(database lessons)');
} catch (PDOException) {
    check('MariaDB reachable', false, '(database lessons)');
}
