<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OrderExtractor;
use App\OrderReview;
use App\Output\Address;
use App\Output\Order;
use App\Output\OrderLine;
use Dotenv\Dotenv;
use NeuronAI\Agent\Observability\Validated;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
use NeuronAI\StructuredOutput\Validation\Validator;
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
function facts(Order $order): array
{
    $lines = array_map(static fn (OrderLine $line): array => [
        $line->sku, $line->quantity, $line->unitPriceCents,
    ], $order->lines);
    usort($lines, static fn (array $a, array $b): int =>
        $a[0] <=> $b[0]);
    $address = $order->deliveryAddress;
    return [
        $order->customerName,
        $lines,
        $order->statedTotalCents,
        $address === null ? null : [
            $address->street, $address->zip, $address->city,
        ],
    ];
}
function candidate(array $source): Order
{
    [$name, $lines, $total, $address] = $source;
    $order = new Order();
    $order->customerName = $name;
    $order->lines = [];
    foreach ($lines as [$sku, $quantity, $price]) {
        $line = new OrderLine();
        $line->sku = $sku;
        $line->quantity = $quantity;
        $line->unitPriceCents = $price;
        $order->lines[] = $line;
    }
    $order->statedTotalCents = $total;
    if ($address !== null) {
        $order->deliveryAddress = new Address();
        [$street, $zip, $city] = $address;
        $order->deliveryAddress->street = $street;
        $order->deliveryAddress->zip = $zip;
        $order->deliveryAddress->city = $city;
    }
    return $order;
}
function review(Order $order, array $catalog): array
{
    $issues = OrderReview::issues($order, $catalog);
    sort($issues);
    return $issues;
}
$catalog = ['BW-1120' => 1250, 'WF-0080' => 4500];
// Synthetic emails and an independently written source oracle.
// Do not supply this oracle or the catalog to the extractor.
$cases = [
    'valid' => [
        'email' => 'I am Avery Stone. Order 2x BW-1120 at '
            . 'EUR 12.50 each and 2x WF-0080 at EUR 45.00 each. '
            . 'Total EUR 115.00. Deliver to 22 Cedar Road, '
            . 'ZIP 04001. No city is stated.',
        'facts' => ['Avery Stone', [
            ['BW-1120', 2, 1250], ['WF-0080', 2, 4500],
        ], 11500, ['22 Cedar Road', '04001', null]],
        'issues' => [],
    ],
    'incomplete' => [
        'email' => 'Please order 4x BW-1120. Use the same '
            . 'customer details and address as last time. '
            . 'No prices or total are provided in this email.',
        'facts' => [null, [['BW-1120', 4, null]], null, null],
        'issues' => [
            'Customer name missing.', 'Delivery address missing.',
            'Stated total missing.', 'Unit price missing: BW-1120.',
        ],
    ],
    'incorrect' => [
        'email' => 'I am Avery Stone. Order 1x BW-1120 at '
            . 'EUR 12.00 and 1x WF-0080 at EUR 45.00. '
            . 'Total EUR 57.00. Deliver to 22 Cedar Road, '
            . 'ZIP 04001. No city is stated.',
        'facts' => ['Avery Stone', [
            ['BW-1120', 1, 1200], ['WF-0080', 1, 4500],
        ], 5700, ['22 Cedar Road', '04001', null]],
        'issues' => [
            'Catalog price differs: BW-1120.',
            'Catalog total differs: expected 5750.',
        ],
    ],
];
try {
    Deserializer::make()->fromJson(
        '{"lines":"not a list"}', Order::class,
    );
    throw new RuntimeException('Malformed shape was accepted.');
} catch (DeserializerException $error) {
    echo 'Shape: malformed lines rejected.' . PHP_EOL;
}
foreach ($cases as $name => $case) {
    $order = candidate($case['facts']);
    check(Validator::validate($order) === [],
        "{$name}: expected valid runtime contract.");
    check(facts($order) === $case['facts'],
        "{$name}: source oracle does not round-trip.");
    $expected = $case['issues'];
    sort($expected);
    check(review($order, $catalog) === $expected,
        "{$name}: unexpected business review.");
    echo "Local {$name}: contract, source, review verified."
        . PHP_EOL;
}
$invalid = candidate($cases['valid']['facts']);
$invalid->lines[0]->sku = 'blue widget';
check(Validator::validate($invalid) !== [],
    'Nested SKU rule was not enforced.');
$invalid->lines[0]->sku = 'BW-1120';
$invalid->lines[0]->quantity = 0;
check(Validator::validate($invalid) !== [],
    'Nested quantity rule was not enforced.');
$invalid->lines = [];
check(Validator::validate($invalid) !== [],
    'An empty order must fail ArrayOf.');
echo 'Runtime rules: malformed nested line and empty order rejected.'
    . PHP_EOL;
$changed = candidate($cases['valid']['facts']);
$changed->lines[0]->quantity = 3;
check(Validator::validate($changed) === [],
    'Positive invented quantity should pass property rules.');
check(facts($changed) !== $cases['valid']['facts'],
    'Source fidelity must detect an invented quantity.');
echo 'Source fidelity: valid but invented quantity detected.'
    . PHP_EOL;
if (!in_array('--live', $argv, true)) {
    echo 'Live extraction skipped. Add --live to run it.' . PHP_EOL;
    echo 'Persistence: not performed.' . PHP_EOL;
    echo 'Exercise complete.' . PHP_EOL;
    exit(0);
}
Dotenv::createImmutable(dirname(__DIR__))->load();
$failed = false;
foreach ($cases as $name => $case) {
    $agent = OrderExtractor::make()->setThreadId(
        'exercise-' . $name . '-' . bin2hex(random_bytes(4)),
    );
    $validations = 0;
    $agent->subscribe(Validated::class,
        function (Validated $event) use (&$validations): void {
            ++$validations;
            echo 'Validation violations: '
                . count($event->violations) . PHP_EOL;
        });
    try {
        $order = $agent->structured(
            new UserMessage($case['email']),
            Order::class, maxRetries: 1,
        );
    } catch (AgentException | DeserializerException $error) {
        echo "Live {$name}: extraction rejected." . PHP_EOL;
        $failed = true;
        continue;
    }
    check($order instanceof Order,
        "{$name}: expected an Order.");
    check(Validator::validate($order) === [],
        "{$name}: invalid runtime contract.");
    check(facts($order) === $case['facts'],
        "{$name}: output differs from explicit source facts.");
    $expected = $case['issues'];
    sort($expected);
    check(review($order, $catalog) === $expected,
        "{$name}: unexpected business review.");
    echo "Live {$name}: contract, source, review verified."
        . PHP_EOL;
    echo "Validation events: {$validations}" . PHP_EOL;
    foreach ($expected as $issue) {
        echo $issue . PHP_EOL;
    }
}
echo 'Persistence: not performed.' . PHP_EOL;
echo $failed ? 'Exercise failed.' . PHP_EOL
    : 'Exercise complete.' . PHP_EOL;
exit($failed ? 1 : 0);
