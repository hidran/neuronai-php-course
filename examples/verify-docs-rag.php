<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/MarkdownSectionSplitter.php';
require __DIR__ . '/DocsAgent.php';
require __DIR__ . '/DocsSync.php';
require __DIR__ . '/KeywordEmbeddings.php';
use App\Docs\DocsAgent;
use App\Docs\DocsSync;
use App\Docs\MarkdownSectionSplitter;
use App\Rag\KeywordEmbeddings;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\Document;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use NeuronAI\Testing\FakeAIProvider;
function check(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException("FAILED: {$label}");
    }
}
$splitter = new MarkdownSectionSplitter();
check($splitter->splitDocument(new Document('')) === [], 'empty document');
$accented = str_repeat('è così ', 200);
$pieces = $splitter->splitDocument(new Document("## A\n\n{$accented}")
    ->setSourceName('a.md')->setMetadata(['team' => 'docs']));
check(count($pieces) > 1 && array_all($pieces, static fn (Document $p): bool
    => mb_strlen($p->getContent()) <= 600 && $p->getSourceName() === 'a.md'
    && $p->getMetadata() === ['team' => 'docs']
    && !str_contains($p->getContent(), "\xC3\n")), 'multibyte safe wrap');
$fake = new FakeAIProvider(new AssistantMessage('Set retry_limit (setup.md)'),
    new AssistantMessage('The documentation does not cover it.'));
$store = new MemoryVectorStore(topK: 3);
$agent = DocsAgent::make($fake, new KeywordEmbeddings(), $store, 0.5)
    ->setThreadId('t');
$work = sys_get_temp_dir() . '/docs-' . bin2hex(random_bytes(4));
mkdir($work);
$sync = new DocsSync($agent, $store, $splitter, "{$work}/manifest.json");
$sync->sync(__DIR__ . '/fixtures/docs');
check($sync->indexedSources() === ['billing.md', 'setup.md', 'support.md'],
    'manifest lists the three sources');
$hits = $agent->resolveRetrieval()->retrieve(
    new UserMessage('Where do we set the retry limit?'));
check($hits !== [] && $hits[0]->getSourceName() === 'setup.md', 'retrieval');
$agent->chat(new UserMessage('Where do we set the retry limit?'));
$sent = json_encode($fake->getRecorded()[0]->messages);
check(str_contains($sent, 'retry'), 'passage reaches the model');
DocsAgent::make($fake, new KeywordEmbeddings(), $store, 0.5)->setThreadId('u')
    ->chat(new UserMessage('Can Tidepool export reports to Excel?'));
$sent = json_encode($fake->getRecorded()[1]->messages);
check(!str_contains($sent, 'setup.md'), 'threshold keeps unrelated passages out');
array_map(unlink(...), glob("{$work}/*"));
rmdir($work);
echo 'DOCS RAG OK' . PHP_EOL;
