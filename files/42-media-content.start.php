<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\MediaAgent;
use App\MediaFixtures;
use Dotenv\Dotenv;
use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\UserMessage;
Dotenv::createImmutable(dirname(__DIR__))->load();
$fixtures = new MediaFixtures(dirname(__DIR__) . '/fixtures/media');
$agent = MediaAgent::make()->setThreadId($argv[1] ?? 'image');
// READ_IMAGE_BYTES
// ADD_IMAGE_BLOCK
foreach ($image->getContentBlocks() as $block) {
    echo $block::class . PHP_EOL;
    if ($block instanceof ImageContent) {
        echo 'Media type: ' . $block->mediaType . PHP_EOL;
    }
}
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
$pdf = new UserMessage('Dispatch code?');
$bytes = file_get_contents($fixtures->path('note.pdf', 'application/pdf'));
if ($bytes === false) {
    throw new RuntimeException('PDF read failed.');
}
// ADD_PDF_BLOCK
$mode = $argv[1] ?? 'image';
if ($mode === 'inspect') {
    echo 'Image structure ' . 'complete.' . PHP_EOL;
    exit(0);
}
if ($mode === 'blocks') {
    $combined = new UserMessage([
        $image->getContentBlocks()[0],
        $image->getContentBlocks()[1],
        $pdf->getContentBlocks()[1],
    ]);
    foreach ($combined->getContentBlocks() as $block) {
        echo 'Ordered: ' . $block::class . PHP_EOL;
    }
    echo 'Ordered blocks ' . 'complete.' . PHP_EOL;
    exit(0);
}
if (!in_array($mode, ['image', 'pdf'], true)) {
    throw new RuntimeException('Use image, pdf, inspect or blocks.');
}
$message = $mode === 'pdf' ? $pdf : $image;
$reply = $agent->chat($message)->getMessage()?->getContent() ?? '';
echo $reply . PHP_EOL;
$fixtures->verify($reply, $mode);
