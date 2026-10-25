<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/MediaFixtures.php';
require __DIR__ . '/MediaAgent.php';
use App\Agents\MediaAgent;
use App\MediaFixtures;
use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
$fixtures = new MediaFixtures(__DIR__);
foreach ([['../x.png', 'image/png'], ['shapes.png', 'application/pdf'],
    ['missing.png', 'image/png']] as [$name, $mime]) {
    try {
        $fixtures->path($name, $mime);
        throw new LogicException("accepted {$name}");
    } catch (RuntimeException) {
    }
}
$encoded = $fixtures->encoded('shapes.png', 'image/png');
$raw = file_get_contents(__DIR__ . '/shapes.png');
if (base64_decode($encoded, true) !== $raw) {
    throw new RuntimeException('encoding round trip failed');
}
$message = new UserMessage('Triangle color and label?');
$message->addContent(new ImageContent($encoded, SourceType::BASE64,
    MediaType::PNG));
$fake = new FakeAIProvider(
    new AssistantMessage('Green, labeled CEDAR-47.'),
);
$reply = MediaAgent::make($fake)->setThreadId('t')->chat($message)
    ->getMessage()?->getContent() ?? '';
$sent = $fake->getRecorded()[0]->messages[0]->getContentBlocks();
if (count($sent) !== 2 || !$sent[1] instanceof ImageContent) {
    throw new RuntimeException('image block not sent');
}
if ($fixtures->facts('image') !== ['green', 'CEDAR-47']) {
    throw new RuntimeException('expected.json changed');
}
$fixtures->verify($reply, 'image');
try {
    $fixtures->verify('No idea.', 'image');
    throw new LogicException('wrong reply accepted');
} catch (RuntimeException) {
}
echo 'MEDIA OK' . PHP_EOL;
