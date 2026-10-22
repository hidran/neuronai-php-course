<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\MediaAgent;
use App\MediaFixtures;
use Dotenv\Dotenv;
use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\UserMessage;
$directory = dirname(__DIR__) . '/fixtures/media';
$fixtures = new MediaFixtures($directory);
$facts = json_decode(
    file_get_contents($directory . '/expected.json'),
    true, flags: JSON_THROW_ON_ERROR,
);
$failed = false;
try {
    $fixtures->path('../outside.png', 'image/png');
} catch (RuntimeException) {
    $failed = true;
}
if (!$failed) {
    throw new RuntimeException('Traversal was not refused.');
}
$failed = false;
try {
    $fixtures->path('shapes.png', 'application/pdf');
} catch (RuntimeException) {
    $failed = true;
}
if (!$failed) {
    throw new RuntimeException('Wrong format was not refused.');
}
$image = new UserMessage('Circle label?');
$image->addContent(new ImageContent(
    content: base64_encode(file_get_contents(
        $fixtures->path('shapes.png', 'image/png')
    )),
    sourceType: SourceType::BASE64,
    mediaType: MediaType::PNG,
));
$pdf = new UserMessage('Crates, dock and review word?');
$pdf->addContent(new FileContent(
    content: base64_encode(file_get_contents(
        $fixtures->path('note.pdf', 'application/pdf')
    )),
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
Dotenv::createImmutable(dirname(__DIR__))->load();
$queries = [
    'image' => [$image, [$facts['image']['circle_label']]],
    'pdf' => [$pdf, [
        (string) $facts['pdf']['crates'],
        $facts['pdf']['dock'], $facts['pdf']['review_word'],
    ]],
];
$normalize = static fn (string $s): string => strtolower(
    preg_replace('/[^a-zA-Z0-9]/', '', $s)
);
foreach ($queries as $kind => [$message, $expected]) {
    $agent = MediaAgent::make()->setThreadId('exercise-' . $kind);
    $reply = $agent->chat($message)->getMessage()?->getContent() ?? '';
    echo $kind . ': ' . $reply . PHP_EOL;
    foreach ($expected as $fact) {
        if (!str_contains($normalize($reply), $normalize($fact))) {
            throw new RuntimeException('Source fact missing: ' . $kind);
        }
    }
    echo ucfirst($kind) . ' exercise facts verified.' . PHP_EOL;
}
echo 'Exercise complete.' . PHP_EOL;
