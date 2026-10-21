<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\HelloAgent;
use Dotenv\Dotenv;
Dotenv::createImmutable(dirname(__DIR__))->load();
$agent = HelloAgent::make();
$parts = [$agent, $agent->getProvider(), ...$agent->getTools()];
foreach ($parts as $part) {
    echo $part::class, PHP_EOL;
    foreach (class_implements($part) as $contract) {
        echo "  implements $contract", PHP_EOL;
    }
}
