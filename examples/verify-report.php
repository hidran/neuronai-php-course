<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/PackageReport.php';
$tool = '/../../02-02-our-first-tool/files/PackageVersionTool.php';
require __DIR__ . $tool;
use App\Output\PackageReport;
use App\PackageVersionTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
$call = new ToolCall('get_package_version', 'call-1',
    ['package' => 'vlucas/phpdotenv']);
$json = '{"package":"vlucas/phpdotenv","version":"5.6.1",'
    . '"summary":"Loads environment variables from .env files."}';
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [$call]), new AssistantMessage($json),
);
$report = Agent::make()->setAiProvider($fake)
    ->setInstructions(new SystemMessage('Use the tool, then report.'))
    ->addTool(new PackageVersionTool())
    ->setThreadId('t')
    ->structured(new UserMessage('Report on vlucas/phpdotenv.'),
        PackageReport::class);
if (!$report instanceof PackageReport || $report->version !== '5.6.1') {
    throw new RuntimeException('structured() with a tool failed');
}
$records = $fake->getRecorded();
if (count($records) !== 2 || $records[0]->method !== 'structured'
    || $records[1]->method !== 'structured') {
    throw new RuntimeException('expected two structured calls');
}
if ($records[0]->tools === [] || $records[1]->tools === []) {
    throw new RuntimeException('tool not offered to the provider');
}
$seen = false;
foreach ($records[1]->messages as $message) {
    $content = json_encode($message->jsonSerialize());
    if (str_contains((string) $content, 'is at version')) {
        $seen = true;
    }
}
if (!$seen) {
    throw new RuntimeException('tool result missing from second call');
}
echo 'REPORT OK' . PHP_EOL;
