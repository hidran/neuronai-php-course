<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use App\Output\Delivery;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$message = new UserMessage(
    'I am Ada Turner. Deliver to 12 Maple Street, ZIP 02108. '
    . 'Tags: urgent, gift. Keywords: fragile, prepaid.'
);
$delivery = PersonExtractor::make()->setThreadId('delivery')
    ->structured($message, Delivery::class);
echo 'Result: ' . $delivery::class . PHP_EOL;
echo 'Address type: ' . $delivery->address::class . PHP_EOL;
echo 'Street: ' . $delivery->address->street . PHP_EOL;
echo 'ZIP: ' . $delivery->address->zip . PHP_EOL;
echo 'City: ' . ($delivery->address->city ?? 'not stated') . PHP_EOL;
foreach ($delivery->tags as $tag) {
    echo 'Tag type: ' . $tag::class . '; ' . $tag->name . PHP_EOL;
}
echo 'Keywords: ' . implode(', ', $delivery->keywords) . PHP_EOL;
echo 'Nested extraction ' . 'complete.' . PHP_EOL;
