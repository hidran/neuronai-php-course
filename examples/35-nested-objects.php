<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use App\Config\ProviderFactory;
use App\Output\Delivery;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$message = new UserMessage(
    'I am Ada Turner. Deliver to 12 Maple Street, ZIP 02108. '
    . 'Tags: urgent with priority 1, gift with priority 3. '
    . 'Keywords: fragile, prepaid.'
);
$delivery = PersonExtractor::make($provider)->setThreadId('delivery')
    ->structured($message, Delivery::class);
echo 'Result: ' . $delivery::class . PHP_EOL;
echo 'Address type: ' . $delivery->address::class . PHP_EOL;
echo 'Street: ' . $delivery->address->street . PHP_EOL;
echo 'ZIP: ' . $delivery->address->zip . PHP_EOL;
echo 'City: ' . ($delivery->address->city ?? 'not stated') . PHP_EOL;
foreach ($delivery->tags as $tag) {
    echo 'Tag type: ' . $tag::class . '; ' . $tag->name
        . ', priority ' . $tag->priority . PHP_EOL;
}
echo 'Keywords: ' . implode(', ', $delivery->keywords) . PHP_EOL;
echo 'Nested extraction complete.' . PHP_EOL;
