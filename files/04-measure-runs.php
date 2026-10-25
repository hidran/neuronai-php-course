<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Config\Settings;
use App\HelloAgent;
use App\Support\Price;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$settings = Settings::fromEnv($_ENV);
$provider = new ProviderFactory($settings)->create();
// Dollars per million tokens. Prices change: check your provider.
$price = new Price(inputPerMillion: 3.0, outputPerMillion: 15.0);
$runs = [
    'hello' => 'What is an AI agent, in plain words?',
    'tool' => 'Which neuron-core/neuron-ai version is installed here?',
];
$costs = [];
foreach ($runs as $name => $question) {
    echo "Run: $name" . PHP_EOL;
    $start = hrtime(true);
    $state = HelloAgent::make($provider)
        ->setThreadId($name)
        ->chat(new UserMessage($question));
    $ms = intdiv(hrtime(true) - $start, 1_000_000);
    $calls = $input = $output = 0;
    foreach ($state->getSteps() as $step) {
        $usage = $step->getUsage();
        if ($usage === null) {
            continue;
        }
        $calls++;
        $input += $usage->inputTokens;
        $output += $usage->outputTokens;
        echo "  Call $calls: {$usage->inputTokens} in, "
            . "{$usage->outputTokens} out" . PHP_EOL;
    }
    $costs[$name] = $price->of($input, $output);
    echo "Calls: $calls, tokens in: $input, out: $output, $ms ms"
        . PHP_EOL . 'Estimated cost: $'
        . number_format($costs[$name], 4) . PHP_EOL;
}
if ($costs['hello'] > 0) {
    echo 'The tool run cost '
        . round($costs['tool'] / $costs['hello'], 1)
        . ' times as much' . PHP_EOL;
}
