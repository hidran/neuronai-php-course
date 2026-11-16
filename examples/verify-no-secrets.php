<?php
declare(strict_types=1);
// Offline check of the lesson "An Inspector account". Usage: php verify-no-secrets.php <lab>
// The lesson has no PHP of its own: what must hold is that no key ever lands in a file we share.
// 1. No text file of the lesson folder contains a key-shaped string, an assigned Inspector key or an email address.
// 2. The lab's .env.example (when the lab has one) keeps INSPECTOR_INGESTION_KEY empty: a name, never a value.
// Only .env.example is opened; a .env file is never read here, its presence in the lesson folder is an error.
$lesson = dirname(__DIR__);
$lab = $argv[1] ?? '';
$problems = [];
$checks = [
    'a key-shaped string' => '/(?<![A-Za-z0-9])[A-Za-z0-9]{32,}(?![A-Za-z0-9])/',
    'an Inspector key with a value' => '/INSPECTOR_(INGESTION_)?KEY[ \t]*=[ \t]*["\']?[A-Za-z0-9]{8,}/',
    'a provider key' => '/(sk-[A-Za-z0-9_-]{20,}|AIza[A-Za-z0-9_-]{30,})/',
    'an email address' => '/[A-Za-z0-9._%+-]+@(?!example\.|mail\.com|inspector\.dev)[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[a-z]{2,}/',
];
$extensions = ['md', 'yaml', 'yml', 'sh', 'py', 'js', 'php', 'json', 'csv', 'tsv', 'txt', 'http'];
$skip = ['audio', 'recordings', 'slides', '__pycache__'];
$files = 0;
$walk = function (string $dir) use (&$walk, &$files, &$problems, $checks, $extensions, $skip, $lesson): void {
    foreach (scandir($dir) ?: [] as $name) {
        if ($name === '.' || $name === '..') {
            continue;
        }
        $path = $dir . '/' . $name;
        $short = substr($path, strlen($lesson) + 1);
        if ($name === '.env') {
            $problems[] = $short . ': a .env file does not belong in a lesson folder';
            continue;
        }
        if (is_dir($path)) {
            if (!in_array($name, $skip, true)) {
                $walk($path);
            }
            continue;
        }
        if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $extensions, true)) {
            continue;
        }
        $files++;
        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $number => $line) {
            foreach ($checks as $what => $pattern) {
                if (preg_match($pattern, $line) === 1) {
                    $problems[] = $short . ':' . ($number + 1) . ': ' . $what;
                }
            }
        }
    }
};
$walk($lesson);
if ($files < 5) {
    $problems[] = 'only ' . $files . ' text files found: wrong folder?';
}
$example = $lab !== '' ? rtrim($lab, '/') . '/.env.example' : '';
$exampleState = 'no lab .env.example to check';
if ($example !== '' && is_file($example)) {
    $found = false;
    foreach (file($example, FILE_IGNORE_NEW_LINES) ?: [] as $number => $line) {
        if (!str_starts_with($line, 'INSPECTOR_INGESTION_KEY=')) {
            continue;
        }
        $found = true;
        if ($line !== 'INSPECTOR_INGESTION_KEY=') {
            $problems[] = '.env.example:' . ($number + 1) . ': INSPECTOR_INGESTION_KEY has a value';
        }
    }
    if (!$found) {
        $problems[] = '.env.example: no INSPECTOR_INGESTION_KEY line';
    }
    $exampleState = '.env.example keeps INSPECTOR_INGESTION_KEY empty';
}
if ($problems !== []) {
    fwrite(STDERR, implode(PHP_EOL, $problems) . PHP_EOL);
    throw new RuntimeException('NO SECRETS CHECK FAILED: ' . count($problems) . ' problem(s)');
}
echo 'lesson files scanned: ' . $files . PHP_EOL;
echo $exampleState . PHP_EOL;
echo 'NO SECRETS OK' . PHP_EOL;
