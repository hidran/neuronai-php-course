<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/../../06-02-output-class/files/Person.php';
require __DIR__ . '/PersonExtractor.default.php';
use App\Agents\PersonExtractor;
use App\Output\Person;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$json = '{"name":"Mia Chen","preference":"sushi","age":null}';
$fake = new FakeAIProvider(new AssistantMessage($json));
$person = PersonExtractor::make($fake)->setThreadId('t')
    ->structured(new UserMessage('My name is Mia Chen.'));
if (!$person instanceof Person || $person->name !== 'Mia Chen') {
    throw new RuntimeException('structured() did not return Person');
}
if ($person->age !== null) {
    throw new RuntimeException('null age lost');
}
$record = $fake->getRecorded()[0];
if ($record->method !== 'structured'
    || $record->structuredClass !== Person::class) {
    throw new RuntimeException('provider not asked for structured');
}
if (!str_contains((string) $record->systemPrompt?->getContent(),
    'explicitly stated')) {
    throw new RuntimeException('instructions missing');
}
echo 'EXTRACTOR OK' . PHP_EOL;
