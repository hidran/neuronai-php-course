<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\SourceExamples;
use Dotenv\Dotenv;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
$root = dirname(__DIR__);
Dotenv::createImmutable($root)->load();
$path = $root . '/fixtures/sources/invoice.pdf';
// Add the URL image block here.
// Add the inline PDF block here.
// Add the placeholder ID block here.
if (($argv[1] ?? '') !== '--live') {
    SourceExamples::summary([$link, $inline, $reference]);
    echo 'Representations inspected; placeholder never sent.' . PHP_EOL;
    exit;
}
$reference = new FileContent(
    SourceExamples::uploadedId($root . '/storage/source-upload.json'),
    SourceType::ID, 'application/pdf', 'invoice.pdf',
);
SourceExamples::summary([$link, $inline, $reference]);
SourceExamples::ask($inline, 'MAPLE-731');
SourceExamples::ask($reference, 'MAPLE-731');
echo 'Source comparison ' . 'complete.' . PHP_EOL;
