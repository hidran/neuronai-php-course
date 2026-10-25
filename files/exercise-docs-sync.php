<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Docs\DocsAgent;
use App\Docs\DocsSync;
use App\Docs\MarkdownSectionSplitter;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\SearchRequest;
// Exercise: a real sync on a private copy of the corpus, with a rename
// and a delete, using the DocsSync service and its manifest.
$rag = require __DIR__ . '/rag-bootstrap.php';
$work = dirname(__DIR__) . '/storage/docs-exercise';
$corpus = "{$work}/corpus";
if (is_dir($work)) {
    removeTree($work);
}
mkdir($corpus, 0o755, true);
foreach (glob(dirname(__DIR__) . '/fixtures/docs/*.md') as $file) {
    copy($file, "{$corpus}/" . basename($file));
}
function removeTree(string $path): void
{
    $entries = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path,
            FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($entries as $entry) {
        $entry->isDir() ? rmdir($entry->getPathname())
            : unlink($entry->getPathname());
    }
    rmdir($path);
}
function check(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException("Check failed: {$label}");
    }
    echo "ok: {$label}\n";
}
$store = new FileVectorStore("{$work}/index", topK: 50,
    name: 'exercise');
$agent = DocsAgent::make($rag->provider, $rag->embeddings, $store);
$sync = new DocsSync($agent, $store, new MarkdownSectionSplitter(),
    "{$work}/manifest.json");
/** @return array<string, int> chunks per stored source */
$stored = static function () use ($store, $rag): array {
    $all = $store->search(new SearchRequest(
        $rag->embeddings->embedText('Tidepool'), topK: 50));
    $counts = array_count_values(array_map(
        static fn ($d): string => $d->getSourceName(), [...$all]));
    ksort($counts);
    return $counts;
};
$sync->sync($corpus);
$first = $stored();
printf("First sync: %d chunks in %d sources\n", array_sum($first),
    count($first));
$sync->sync($corpus);
check($stored() === $first, 'a second sync adds no duplicate');
rename("{$corpus}/billing.md", "{$corpus}/invoices-and-plans.md");
$gone = $sync->sync($corpus);
$renamed = $stored();
check($gone === ['billing.md'] && !isset($renamed['billing.md']),
    'sync removes the renamed source');
check(array_sum($renamed) === array_sum($first),
    'chunk total unchanged');
unlink("{$corpus}/support.md");
$gone = $sync->sync($corpus);
check($gone === ['support.md'] && !isset($stored()['support.md']),
    'sync removes the deleted source');
$questions = require dirname(__DIR__) . '/fixtures/docs-questions.php';
[$question, $expected] = $questions['retry'];
$top = $agent->resolveRetrieval()
    ->retrieve(new UserMessage($question))[0];
check($top->getSourceName() === $expected, 'retry retrieves setup.md');
echo "Docs sync exercise passed.\n";
