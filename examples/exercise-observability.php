<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\ObservedWeatherAgent;
use App\Config\ProviderFactory;
use NeuronAI\Agent\Observability\ToolCalled;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Observability\ObservabilityEvent;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$counts = new ArrayObject(['broad' => 0, 'first' => 0, 'second' => 0]);
$question = 'Use fixture temperatures for Turin and Milan '
    . 'and calculate their average with the mean tool.';
foreach (['first', 'second'] as $label) {
    $agent = ObservedWeatherAgent::make($provider)
        ->setThreadId('exercise-' . $label . '-' . uniqid());
    if ($label === 'first') {
        $agent->subscribe(ObservabilityEvent::class,
            function () use ($counts): void {
                $counts['broad']++;
            });
    }
    $agent->subscribe(ToolCalled::class,
        function (ToolCalled $event) use ($counts, $label): void {
            $counts[$label]++;
            echo $label . ' tool: ' . $event->tool->getName()
                . PHP_EOL;
        });
    $before = $counts['broad'];
    $state = $agent->chat(new UserMessage($question));
    $delta = $counts['broad'] - $before;
    if (($label === 'first' && $delta === 0)
        || ($label === 'second' && $delta !== 0)
        || $counts[$label] < 3
        || $state->getMessage() === null) {
        throw new RuntimeException('Instance coverage check failed.');
    }
    echo "{$label} broad event delta: {$delta}" . PHP_EOL;
}
echo 'Instance monitoring exercise passed.' . PHP_EOL;
