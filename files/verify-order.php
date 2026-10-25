<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/Address.php';
require __DIR__ . '/OrderLine.php';
require __DIR__ . '/Order.php';
require __DIR__ . '/Catalog.php';
require __DIR__ . '/OrderReview.php';
require __DIR__ . '/OrderExtractor.php';
use App\Agents\OrderExtractor;
use App\Orders\Catalog;
use App\Orders\OrderReview;
use App\Output\Address;
use App\Output\Order;
use App\Output\OrderLine;
use NeuronAI\Agent\Observability\Validated;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
use NeuronAI\StructuredOutput\Validation\Validator;
use NeuronAI\Testing\FakeAIProvider;
$review = new OrderReview(Catalog::fromJsonFile(
    __DIR__ . '/catalog.json'
));
$complete = new Order(
    lines: [new OrderLine('BW-1120', 3, 1250),
        new OrderLine('WF-0080', 1, 4500)],
    customerName: 'Jo Turner',
    statedTotalCents: 8250,
    deliveryAddress: new Address('14 Maple Street', '02108'),
);
if ($review->issues($complete) !== []) {
    throw new RuntimeException('complete order flagged');
}
$malformed = new Order(lines: [new OrderLine('blue-widget', 1, 1250)],
    customerName: 'Jo', statedTotalCents: 1250,
    deliveryAddress: new Address('14 Maple Street', '02108'));
if ($review->issues($malformed) !== ['Malformed SKU: blue-widget.']) {
    throw new RuntimeException('malformed SKU not reported by review');
}
if (Validator::validate($malformed) !== []) {
    throw new RuntimeException('SKU format must not be a retry rule');
}
$incomplete = new Order(lines: [new OrderLine('BW-1120', 2),
    new OrderLine('WF-0080', 1)], statedTotalCents: 7000);
$expected = ['Customer name missing.', 'Delivery address missing.',
    'Unit price missing: BW-1120.', 'Unit price missing: WF-0080.'];
if ($review->issues($incomplete) !== $expected) {
    throw new RuntimeException('incomplete issues differ');
}
if (Validator::validate(new Order(lines: [])) === []) {
    throw new RuntimeException('empty order accepted');
}
try {
    Deserializer::make()->fromJson('{"lines":"not a list"}',
        Order::class);
    throw new RuntimeException('malformed shape accepted');
} catch (DeserializerException) {
}
$bad = '{"lines":[{"sku":"BW-1120","quantity":0}]}';
$good = '{"lines":[{"sku":"BW-1120","quantity":3,'
    . '"unitPriceCents":1250}],'
    . '"customerName":"Jo Turner","statedTotalCents":3750,'
    . '"deliveryAddress":{"street":"14 Maple Street","zip":"02108"}}';
$fake = new FakeAIProvider(
    new AssistantMessage($bad), new AssistantMessage($good),
);
$seen = [];
$agent = OrderExtractor::make($fake)->setThreadId('t');
$agent->subscribe(Validated::class,
    function (Validated $event) use (&$seen): void {
        $seen = [...$seen, ...$event->violations];
    });
$order = $agent->structured(new UserMessage('order email'),
    Order::class);
if (!$order instanceof Order || $review->issues($order) !== []) {
    throw new RuntimeException('retried order not accepted');
}
if ($seen === [] || !str_contains($seen[0], 'OrderLine')) {
    throw new RuntimeException('violation event not observed');
}
if ($fake->getCallCount() !== 2) {
    throw new RuntimeException('default retry budget changed');
}
echo 'ORDER OK' . PHP_EOL;
