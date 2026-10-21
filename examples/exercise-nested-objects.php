<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\PersonExtractor;
use App\Output\Address;
use App\Output\Delivery;
use App\Output\Tag;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$message = new UserMessage(
    'I am Mia Chen. Deliver to 7 Cedar Road, ZIP 00560. '
    . 'Tags: office, cold. Keywords: recyclable.'
);
$delivery = PersonExtractor::make()->setThreadId('exercise-delivery')
    ->structured($message, Delivery::class);
if (!$delivery instanceof Delivery
    || !$delivery->address instanceof Address) {
    throw new RuntimeException('Expected Delivery and Address objects.');
}
if ($delivery->name !== 'Mia Chen'
    || $delivery->address->street !== '7 Cedar Road'
    || $delivery->address->zip !== '00560'
    || $delivery->address->city !== null) {
    throw new RuntimeException('Address facts changed or were invented.');
}
$names = [];
foreach ($delivery->tags as $tag) {
    if (!$tag instanceof Tag) {
        throw new RuntimeException('Every tag must be a Tag object.');
    }
    $names[] = $tag->name;
}
sort($names);
if ($names !== ['cold', 'office']
    || $delivery->keywords !== ['recyclable']) {
    throw new RuntimeException('Tag or keyword facts changed.');
}
echo 'Result: ' . $delivery::class . PHP_EOL;
echo 'Address type: ' . $delivery->address::class . PHP_EOL;
echo 'ZIP: ' . $delivery->address->zip . PHP_EOL;
echo 'City: not stated' . PHP_EOL;
echo 'Tags: ' . implode(', ', $names) . PHP_EOL;
echo 'Keywords: ' . implode(', ', $delivery->keywords) . PHP_EOL;
echo 'Exercise complete.' . PHP_EOL;
