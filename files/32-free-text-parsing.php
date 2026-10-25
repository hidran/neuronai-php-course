<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
function inspectAnswer(string $label, string $answer): void
{
    try {
        $data = json_decode($answer, associative: true,
            flags: JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        echo "{$label}: invalid JSON" . PHP_EOL;
        return;
    }
    $valid = is_array($data)
        && is_string($data['name'] ?? null)
        && is_int($data['total'] ?? null);
    $result = $valid ? 'accepted' : 'contract mismatch';
    echo "{$label}: {$result}" . PHP_EOL;
}
$plain = '{"name":"Ada","total":2400}';
$answers = [
    'Plain' => $plain,
    'Fenced' => "```json\n{$plain}\n```",
    'Renamed' => '{"name":"Ada","total_amount":2400}',
    'String' => '{"name":"Ada","total":"2400"}',
];
foreach ($answers as $label => $answer) {
    inspectAnswer($label, $answer);
}
if (($argv[1] ?? '') !== 'live') {
    echo "Fixture check complete." . PHP_EOL;
    exit;
}
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$agent = Agent::make()->setAiProvider($provider)
    ->setInstructions(new SystemMessage(
        'Extract order details. Return only JSON.'
        . ' Keys: name as string, total as integer cents.'
    ))->setThreadId('free-text');
$state = $agent->chat(new UserMessage(
    'Ada ordered two notebooks, 1200 cents each.'
));
$answer = $state->getMessage()?->getContent() ?? '';
echo 'Live answer: ' . $answer . PHP_EOL;
inspectAnswer('Live', $answer);
echo "Live check complete." . PHP_EOL;
