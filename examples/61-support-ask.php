<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Support\SupportAsk;
use App\Support\SupportQuestion;
try {
    $question = SupportQuestion::fromInput($argv[1] ?? '');
} catch (InvalidArgumentException $e) {
    fwrite(STDERR, 'Invalid question: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
$container = require __DIR__ . '/services.php';
echo new SupportAsk($container)($question) . PHP_EOL;
