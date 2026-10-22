<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OrderExtractor;
use App\OrderReview;
use App\Output\Order;
use Dotenv\Dotenv;
use NeuronAI\Agent\Observability\Validated;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
Dotenv::createImmutable(dirname(__DIR__))->load();
$fixtures = dirname(__DIR__) . '/fixtures/orders';
$catalog = json_decode(
    file_get_contents($fixtures . '/catalog.json'),
    true, flags: JSON_THROW_ON_ERROR,
);
$cases = array_slice($argv, 1) ?: [
    'complete', 'incomplete', 'wrong-total', 'malformed',
];
foreach ($cases as $case) {
    echo "Case: {$case}" . PHP_EOL;
    $agent = OrderExtractor::make()->setThreadId($case);
    $agent->subscribe(Validated::class, function (Validated $event) {
        echo 'Validation violations: '
            . count($event->violations) . PHP_EOL;
    });
    try {
        $email = file_get_contents("{$fixtures}/{$case}.txt");
        $order = $agent->structured(
            new UserMessage($email), Order::class, maxRetries: 1,
        );
        echo 'Lines: ' . count($order->lines) . PHP_EOL;
        foreach ($order->lines as $line) {
            $price = $line->unitPriceCents ?? 'not stated';
            echo "{$line->sku}: {$line->quantity} x {$price}"
                . PHP_EOL;
        }
        echo 'Address: ' . ($order->deliveryAddress?->street
            ?? 'not stated') . PHP_EOL;
        $issues = OrderReview::issues($order, $catalog);
        echo $issues === [] ? 'Review checks passed.' . PHP_EOL
            : implode(PHP_EOL, $issues) . PHP_EOL;
    } catch (AgentException | DeserializerException $error) {
        echo 'Extraction rejected.' . PHP_EOL;
    }
    echo "Case finished: {$case}" . PHP_EOL;
}
echo 'Persistence: not performed.' . PHP_EOL;
echo 'Lab complete.' . PHP_EOL;
