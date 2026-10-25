<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\MediaAgent;
use App\Config\ProviderFactory;
use App\MediaFixtures;
use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\UserMessage;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$fixtures = new MediaFixtures(dirname(__DIR__) . '/fixtures/media');
$mode = $argv[1] ?? 'image';
$agent = MediaAgent::make($provider)->setThreadId($mode);
$image = new UserMessage('Triangle color and label?');
$image->addContent(new ImageContent(
    content: $fixtures->encoded('shapes.png', 'image/png'),
    sourceType: SourceType::BASE64,
    mediaType: MediaType::PNG,
));
foreach ($image->getContentBlocks() as $block) {
    echo $block::class . PHP_EOL;
    if ($block instanceof ImageContent) {
        echo 'Media type: ' . $block->mediaType . PHP_EOL;
    }
}
$pdf = new UserMessage('Dispatch code?');
// ADD_PDF_BLOCK
$messages = ['image' => $image, 'pdf' => $pdf];
if ($mode === 'blocks') {
    $combined = new UserMessage([
        ...$image->getContentBlocks(),
        $pdf->getContentBlocks()[1],
    ]);
    foreach ($combined->getContentBlocks() as $block) {
        echo 'Ordered: ' . $block::class . PHP_EOL;
    }
} elseif (isset($messages[$mode])) {
    $state = $agent->chat($messages[$mode]);
    $reply = $state->getMessage()?->getContent() ?? '';
    echo $reply . PHP_EOL;
    $fixtures->verify($reply, $mode);
    echo 'Attachment facts verified.' . PHP_EOL;
} elseif ($mode !== 'inspect') {
    throw new RuntimeException('Use image, pdf, inspect or blocks.');
}
echo "Mode {$mode} complete." . PHP_EOL;
