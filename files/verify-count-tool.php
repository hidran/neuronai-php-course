<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/CountMessagesTool.php';
use App\Tools\CountMessagesTool;
$pdo = new PDO('sqlite::memory:');
$pdo->exec('CREATE TABLE chat_messages (thread_id TEXT)');
$pdo->exec("INSERT INTO chat_messages VALUES ('demo'), ('demo'), ('x')");
$tool = new CountMessagesTool($pdo);
if ((string) $tool('demo') !== 'Stored messages on thread demo: 2.') {
    throw new RuntimeException('count wrong: ' . $tool('demo'));
}
if (!str_contains(json_encode($tool->getInputSchema()), '"thread"')) {
    throw new RuntimeException('schema lacks thread');
}
echo 'COUNT TOOL OK' . PHP_EOL;
