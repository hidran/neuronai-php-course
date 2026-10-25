<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OrderExtractor;
use App\Config\ProviderFactory;
use App\Orders\Catalog;
use App\Orders\OrderReview;
use App\Output\Order;
use NeuronAI\Agent\Observability\Validated;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$fixtures = dirname(__DIR__) . '/fixtures/orders';
$review = new OrderReview(Catalog::fromJsonFile(
    $fixtures . '/catalog.json'
));
$cases = array_slice($argv, 1) ?: [
    'complete', 'incomplete', 'wrong-total', 'malformed',
];
foreach ($cases as $case) {
    echo "Case: {$case}" . PHP_EOL;
    $agent = OrderExtractor::make($provider)->setThreadId($case);
    $agent->subscribe(Validated::class, function (Validated $event) {
        foreach ($event->violations as $violation) {
            echo 'Violation: ' . $violation . PHP_EOL;
        }
    });
    try {
        $email = file_get_contents("{$fixtures}/{$case}.txt")
            ?: throw new RuntimeException("Missing email {$case}.");
        $order = $agent->structured(
            new UserMessage($email), Order::class,
        );
        echo 'Lines: ' . count($order->lines) . PHP_EOL;
        foreach ($order->lines as $line) {
            $price = $line->unitPriceCents ?? 'not stated';
            echo "{$line->sku}: {$line->quantity} x {$price}"
                . PHP_EOL;
        }
        echo 'Address: ' . ($order->deliveryAddress?->street
            ?? 'not stated') . PHP_EOL;
        $issues = $review->issues($order);
        echo $issues === [] ? 'Review checks passed.' . PHP_EOL
            : implode(PHP_EOL, $issues) . PHP_EOL;
    } catch (DeserializerException $error) {
        echo 'Shape rejected: ' . $error->getMessage() . PHP_EOL;
    } catch (AgentException $error) {
        echo 'Rules rejected after the retry.' . PHP_EOL;
    }
    echo "Case finished: {$case}" . PHP_EOL;
}
echo 'Persistence: not performed.' . PHP_EOL;
echo 'Lab complete.' . PHP_EOL;
