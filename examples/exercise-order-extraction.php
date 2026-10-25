<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\OrderExtractor;
use App\Config\ProviderFactory;
use App\Orders\Catalog;
use App\Orders\OrderReview;
use App\Output\Address;
use App\Output\Order;
use App\Output\OrderLine;
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
    return new Order(
        lines: array_map(
            static fn (array $line): OrderLine
                => new OrderLine(...$line),
            $lines,
        ),
        customerName: $name,
        statedTotalCents: $total,
        deliveryAddress: $address === null ? null
            : new Address(...$address),
    );
}
function sorted(array $issues): array
{
    sort($issues);
    return $issues;
}
$review = new OrderReview(new Catalog([
    'BW-1120' => 1250, 'WF-0080' => 4500,
]));
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
    check(sorted($review->issues($order)) === sorted($case['issues']),
        "{$name}: unexpected business review.");
    echo "Local {$name}: contract, source, review verified."
        . PHP_EOL;
}
$malformed = candidate(['Avery Stone', [['blue widget', 2, 1250]],
    2500, ['22 Cedar Road', '04001', null]]);
check(Validator::validate($malformed) === [],
    'SKU spelling is a review concern, not a retry rule.');
check($review->issues($malformed) === ['Malformed SKU: blue widget.'],
    'Malformed SKU was not reported by the review.');
check(Validator::validate(candidate(['Avery Stone',
    [['BW-1120', 0, 1250]], 0, null])) !== [],
    'Nested quantity rule was not enforced.');
check(Validator::validate(new Order(lines: [])) !== [],
    'An empty order must fail ArrayOf.');
echo 'Runtime rules: zero quantity and empty order rejected.'
    . PHP_EOL;
$changed = $cases['valid']['facts'];
$changed[1][0][1] = 3;
$invented = candidate($changed);
check(Validator::validate($invented) === [],
    'Positive invented quantity should pass property rules.');
check(facts($invented) !== $cases['valid']['facts'],
    'Source fidelity must detect an invented quantity.');
echo 'Source fidelity: valid but invented quantity detected.'
    . PHP_EOL;
if (!in_array('--live', $argv, true)) {
    echo 'Live extraction skipped. Add --live to run it.' . PHP_EOL;
    echo 'Persistence: not performed.' . PHP_EOL;
    echo 'Exercise complete.' . PHP_EOL;
    exit(0);
}
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$failed = false;
foreach ($cases as $name => $case) {
    $agent = OrderExtractor::make($provider)->setThreadId(
        'exercise-' . $name . '-' . bin2hex(random_bytes(4)),
    );
    $validations = 0;
    $agent->subscribe(Validated::class,
        function (Validated $event) use (&$validations): void {
            ++$validations;
            foreach ($event->violations as $violation) {
                echo 'Violation: ' . $violation . PHP_EOL;
            }
        });
    try {
        $order = $agent->structured(
            new UserMessage($case['email']), Order::class,
        );
    } catch (AgentException | DeserializerException $error) {
        echo "Live {$name}: extraction rejected." . PHP_EOL;
        $failed = true;
        continue;
    }
    check(Validator::validate($order) === [],
        "{$name}: invalid runtime contract.");
    check(facts($order) === $case['facts'],
        "{$name}: output differs from explicit source facts.");
    check(sorted($review->issues($order)) === sorted($case['issues']),
        "{$name}: unexpected business review.");
    echo "Live {$name}: contract, source, review verified."
        . PHP_EOL;
    echo "Validation events: {$validations}" . PHP_EOL;
    foreach (sorted($case['issues']) as $issue) {
        echo $issue . PHP_EOL;
    }
}
echo 'Persistence: not performed.' . PHP_EOL;
echo $failed ? 'Exercise failed.' . PHP_EOL
    : 'Exercise complete.' . PHP_EOL;
exit($failed ? 1 : 0);
