<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/SourceAgent.php';
require __DIR__ . '/SourceExamples.php';
require __DIR__ . '/FilesApiClient.php';
use App\Agents\SourceAgent;
use App\SourceExamples;
use App\Http\FilesApiClient;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\HttpClient\HttpRequest;
use NeuronAI\HttpClient\HttpResponse;
use NeuronAI\HttpClient\StreamInterface;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\Anthropic\MessageMapper;
use NeuronAI\Testing\FakeAIProvider;
$encoded = SourceExamples::encoded(__DIR__ . '/invoice.pdf');
$inline = new FileContent($encoded, SourceType::BASE64,
    'application/pdf', 'invoice.pdf');
$reference = new FileContent('file_0123456789abcdef', SourceType::ID,
    'application/pdf', 'invoice.pdf');
$question = new TextContent('What is the invoice review code?');
$mapped = new MessageMapper()->map([new UserMessage([$question,
    $reference])]);
$document = $mapped[0]['content'][1];
$fileId = $document['source']['file_id'] ?? null;
if ($fileId !== 'file_0123456789abcdef') {
    throw new RuntimeException('Anthropic mapper lost the file_id: '
        . json_encode($document));
}
$small = SourceExamples::mappedBytes(new UserMessage([$question,
    $reference]));
$big = SourceExamples::mappedBytes(new UserMessage([$question,
    $inline]));
if ($small >= $big) {
    throw new RuntimeException("reference {$small} vs inline {$big}");
}
$upload = tempnam(sys_get_temp_dir(), 'upload');
file_put_contents($upload, '{"id":"file_abc","type":"file"}');
if (SourceExamples::uploadedId($upload) !== 'file_abc') {
    throw new RuntimeException('upload id not read');
}
file_put_contents($upload, '{"id":"file-openai"}');
try {
    SourceExamples::uploadedId($upload);
    throw new LogicException('wrong id prefix accepted');
} catch (RuntimeException) {
}
unlink($upload);
$fake = new FakeAIProvider(new AssistantMessage('MAPLE-731'));
ob_start();
new SourceExamples(SourceAgent::make($fake))->ask($inline, 'MAPLE-731');
$output = ob_get_clean();
if (!str_contains($output, 'BASE64 document verified.')) {
    throw new RuntimeException("ask() output: {$output}");
}
$seen = new ArrayObject();
$stub = new class($seen) implements HttpClientInterface {
    public function __construct(private ArrayObject $seen)
    {
    }
    public function request(HttpRequest $request): HttpResponse
    {
        $this->seen[] = $request->headers;
        return new HttpResponse(200, '{}');
    }
    public function stream(HttpRequest $request): StreamInterface
    {
        throw new LogicException('not used');
    }
};
new FilesApiClient($stub)->request(HttpRequest::post('https://x', [],
    ['x-api-key' => 'k']));
$headers = $seen[0];
if (($headers['anthropic-beta'] ?? null) !== 'files-api-2025-04-14'
    || ($headers['x-api-key'] ?? null) !== 'k') {
    throw new RuntimeException('beta header: ' . json_encode($headers));
}
echo 'SOURCES OK' . PHP_EOL;
