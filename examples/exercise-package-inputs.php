<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Tools\CheckPackagesTool;
use NeuronAI\Tools\ToolOutput;
// The agent path: NeuronAI casts and checks the inputs, then calls __invoke().
$agentPath = function (mixed $requirements): ToolOutput|string {
    $tool = new CheckPackagesTool()
        ->setInputs(['requirements' => $requirements]);
    $tool->execute();
    return $tool->getResult();
};
$isError = fn (ToolOutput|string $result): bool
    => $result instanceof ToolOutput && $result->isError();
$cases = [
    'Numeric minimum' => [
        ['package' => 'vlucas/phpdotenv', 'minimum' => 5.6],
    ],
    'Missing package' => [['minimum' => '5.6']],
    'Plain string' => 'vlucas/phpdotenv',
];
foreach ($cases as $label => $requirements) {
    $result = $agentPath($requirements);
    $state = $isError($result) ? 'error' : 'ok';
    echo "{$label}: {$state} | {$result}" . PHP_EOL;
}
// A direct call: nothing checks the shape, the defaults still apply.
$direct = new CheckPackagesTool()([['minimum' => '5.6']]);
echo "Direct call, missing package: {$direct}" . PHP_EOL;
// maxItems is a hint in the schema: count the lines that come back.
$eight = array_fill(0, 8, ['package' => 'vlucas/phpdotenv']);
$result = $agentPath($eight);
$lines = substr_count((string) $result, "\n") + 1;
echo 'Eight requirements: ' . ($isError($result)
    ? "rejected | {$result}"
    : "{$lines} report lines") . PHP_EOL;
echo 'Package input exercise complete.' . PHP_EOL;
