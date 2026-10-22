<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\SourceExamples;
use Dotenv\Dotenv;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\OpenAI\MessageMapper;
function check(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}
$root = dirname(__DIR__);
Dotenv::createImmutable($root)->load();
$path = $root . '/fixtures/sources/exercise-invoice.pdf';
$encoded = SourceExamples::encoded($path);
check(base64_decode($encoded, true) === file_get_contents($path),
    'Inline bytes do not round trip.');
$readFailed = false;
try {
    SourceExamples::encoded($root . '/fixtures/sources/missing.pdf');
} catch (RuntimeException) {
    $readFailed = true;
}
check($readFailed, 'Failed reads must be rejected before encoding.');
$unsupported = new FileContent('https://example.com/invoice.pdf',
    SourceType::URL, 'application/pdf', 'invoice.pdf');
$mapped = (new MessageMapper())->map([new UserMessage([
    new TextContent('Find the review code.'), $unsupported,
])]);
check(count($mapped[0]['content']) === 1,
    'Recheck installed mapper: URL file behavior changed.');
echo 'Byte round trip and failed read: passed' . PHP_EOL;
echo 'Unsupported file URL omission detected: passed' . PHP_EOL;
$id = SourceExamples::uploadedId($root . '/storage/exercise-upload.json');
$inline = new FileContent($encoded, SourceType::BASE64,
    'application/pdf', 'exercise-invoice.pdf');
$reference = new FileContent($id, SourceType::ID,
    'application/pdf', 'exercise-invoice.pdf');
$shapes = [];
foreach ([$inline, $reference] as $block) {
    $shape = (new MessageMapper())->map([new UserMessage([$block])]);
    $shapes[$block->sourceType->name] = $shape[0]['content'][0]['file'];
}
check(isset($shapes['BASE64']['file_data']), 'Missing inline route.');
check(($shapes['ID']['file_id'] ?? '') === $id, 'Wrong ID route.');
check(!isset($shapes['ID']['file_data']), 'ID unexpectedly embeds bytes.');
check(strlen(json_encode($shapes['ID']))
    < strlen(json_encode($shapes['BASE64'])), 'Reference is not smaller.');
echo 'Mapped inline and ID representations differ: passed' . PHP_EOL;
SourceExamples::ask($inline, 'BIRCH-964');
SourceExamples::ask($reference, 'BIRCH-964');
echo 'Source reference exercise complete.' . PHP_EOL;
