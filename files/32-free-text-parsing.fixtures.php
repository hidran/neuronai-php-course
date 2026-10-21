<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\Anthropic\Anthropic;
function inspectAnswer(string $label, string $answer): void
{
    try {
        $data = json_decode($answer, true, flags:
            JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        echo "{$label}: invalid JSON" . PHP_EOL;
        return;
    }
    $valid = is_array($data)
        && isset($data['name'], $data['total'])
        && is_string($data['name'])
        && is_int($data['total']);
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
