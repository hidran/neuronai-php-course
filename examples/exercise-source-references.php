<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\SourceAgent;
use App\Config\ProviderFactory;
use App\Http\FilesApiClient;
use App\SourceExamples;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\HttpClient\Curl\CurlHttpClient;
use NeuronAI\Chat\Messages\UserMessage;
function check(bool $value, string $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}
$settings = require __DIR__ . '/bootstrap.php';
$root = dirname(__DIR__);
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
echo 'Byte round trip and failed read: passed' . PHP_EOL;
$upload = $root . '/storage/exercise-upload.json';
$id = SourceExamples::uploadedId($upload);
$inline = new FileContent($encoded, SourceType::BASE64,
    'application/pdf', 'exercise-invoice.pdf');
$reference = new FileContent($id, SourceType::ID,
    'application/pdf', 'exercise-invoice.pdf');
$question = new TextContent('Find the review code.');
$inlineBytes = SourceExamples::mappedBytes(
    new UserMessage([$question, $inline]));
$referenceBytes = SourceExamples::mappedBytes(
    new UserMessage([$question, $reference]));
check($referenceBytes < $inlineBytes,
    'The file reference must be smaller than the inline bytes.');
echo 'Mapped inline and ID representations differ: passed' . PHP_EOL;
$provider = new ProviderFactory($settings)->create()
    ->setHttpClient(new FilesApiClient(new CurlHttpClient()));
$examples = new SourceExamples(SourceAgent::make($provider));
$examples->ask($inline, 'BIRCH-964');
$examples->ask($reference, 'BIRCH-964');
echo 'Source reference exercise complete.' . PHP_EOL;
