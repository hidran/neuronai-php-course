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
$directory = dirname(__DIR__) . '/fixtures/media';
$fixtures = new MediaFixtures($directory);
$facts = json_decode(
    file_get_contents($directory . '/expected.json'),
    associative: true, flags: JSON_THROW_ON_ERROR,
);
foreach ([['../outside.png', 'image/png'],
    ['shapes.png', 'application/pdf']] as [$name, $mime]) {
    try {
        $fixtures->path($name, $mime);
        throw new RuntimeException("Accepted {$name} as {$mime}.");
    } catch (RuntimeException $error) {
        if (str_starts_with($error->getMessage(), 'Accepted')) {
            throw $error;
        }
    }
}
$image = new UserMessage('Circle label?');
$image->addContent(new ImageContent(
    content: $fixtures->encoded('shapes.png', 'image/png'),
    sourceType: SourceType::BASE64,
    mediaType: MediaType::PNG,
));
$pdf = new UserMessage('Crates, dock and review word?');
$pdf->addContent(new FileContent(
    content: $fixtures->encoded('note.pdf', 'application/pdf'),
    sourceType: SourceType::BASE64,
    mediaType: MediaType::PDF,
    filename: 'note.pdf',
));
if (count($image->getContentBlocks()) !== 2
    || !$image->getContentBlocks()[1] instanceof ImageContent
    || !$pdf->getContentBlocks()[1] instanceof FileContent) {
    throw new RuntimeException('Content order changed.');
}
echo 'Local shape, file and format checks passed.' . PHP_EOL;
if (!in_array('--live', $argv, true)) {
    echo 'Live queries skipped; pass --live to verify facts.' . PHP_EOL;
    exit(0);
}
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$queries = [
    'image' => [$image, [$facts['image']['circle_label']]],
    'pdf' => [$pdf, [
        (string) $facts['pdf']['crates'],
        $facts['pdf']['dock'], $facts['pdf']['review_word'],
    ]],
];
foreach ($queries as $kind => [$message, $expected]) {
    $agent = MediaAgent::make($provider)
        ->setThreadId('exercise-' . $kind);
    $reply = $agent->chat($message)->getMessage()?->getContent() ?? '';
    echo $kind . ': ' . $reply . PHP_EOL;
    foreach ($expected as $fact) {
        if (!str_contains(MediaFixtures::normalize($reply),
            MediaFixtures::normalize($fact))) {
            throw new RuntimeException('Source fact missing: ' . $kind);
        }
    }
    echo ucfirst($kind) . ' exercise facts verified.' . PHP_EOL;
}
echo 'Exercise complete.' . PHP_EOL;
