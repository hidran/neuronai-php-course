<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
foreach (['Authority', 'AuthorityRouter', 'OrderDatabase', 'OrderStatusTool',
    'SearchDocsTool', 'SupportAgent', 'AuthorityReport'] as $class) {
    require __DIR__ . "/{$class}.php";
}
require __DIR__ . '/../../11-03-vector-stores-and-retrieval/files/KeywordEmbeddings.php';
use App\Knowledge\Authority;
use App\Knowledge\AuthorityReport;
use App\Knowledge\AuthorityRouter;
use App\Knowledge\OrderDatabase;
use App\Knowledge\OrderStatusTool;
use App\Knowledge\SearchDocsTool;
use App\Knowledge\SupportAgent;
use App\Rag\KeywordEmbeddings;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\DataLoader\FileDataLoader;
use NeuronAI\RAG\Retrieval\SimilarityRetrieval;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
function check(bool $ok, string $what): void
{
    if (!$ok) {
        throw new RuntimeException("FAILED: {$what}");
    }
}
$dbPath = sys_get_temp_dir() . '/orders-' . bin2hex(random_bytes(4)) . '.sqlite';
$pdo = new OrderDatabase($dbPath)->pdo();
$pdo->exec((string) file_get_contents(__DIR__ . '/fixtures/knowledge/orders.sql'));
$router = new AuthorityRouter([
    'direct context' => ['authority' => Authority::ShortStableText,
        'accepting' => ['input tokens']],
    'retrieval' => ['authority' => Authority::Document,
        'accepting' => ['ingestion', 'retrieval evaluation']],
    'tools' => ['authority' => Authority::Record,
        'accepting' => ['validation', 'error handling', 'authorization']],
    'fine tuning' => ['authority' => Authority::LearnedBehavior,
        'accepting' => ['training data']],
]);
check($router->mechanismFor(Authority::Record) === 'tools', 'router');
check($router->accepting('retrieval') === ['ingestion', 'retrieval evaluation'],
    'accepting');
ob_start();
new AuthorityReport($router, $pdo)
    ->print(__DIR__ . '/fixtures/knowledge/requests.json');
$out = ob_get_clean();
foreach (['Decision table: 4 mechanisms', 'authority document -> retrieval',
    'order_status as C-118: Order 4471 is refunded, 49.00 USD',
    'order_status as C-204: No order 4471 for this customer',
    'Authority check complete.'] as $line) {
    check(str_contains($out, $line), "report prints '{$line}'");
}
$tool = new OrderStatusTool($pdo, 'C-118');
check(str_starts_with($tool(order_id: 4472)->getText(), 'Order 4472 is shipped'),
    'tool exact row');
check($tool(order_id: 5120)->isError(), 'other customer refused');
check($tool(order_id: 0)->isError(), 'invalid number rejected');
$embeddings = new KeywordEmbeddings();
$policy = FileDataLoader::for(__DIR__ . '/fixtures/knowledge/refund-policy.md')
    ->getDocuments();
foreach ($policy as $document) {
    $document->setSourceName(basename($document->getSourceName()));
}
$store = new MemoryVectorStore(topK: 1);
$fake = new FakeAIProvider(
    new ToolCallMessage(null, [new ToolCall('order_status', 'c1',
        ['order_id' => 4471])]),
    new AssistantMessage('Order 4471 was refunded; banks take 5 days.'),
);
$agent = SupportAgent::make($fake, $embeddings, $store, $pdo, 'C-118')
    ->setThreadId('t');
$agent->reindexBySource($policy);
$state = $agent->chat(new UserMessage('Was order 4471 refunded?'));
check($state->getToolRuns('order_status') === 1, 'order tool ran');
$sent = json_encode($fake->getRecorded()[0]->messages);
check(str_contains($sent, 'refund-policy.md'), 'policy passage in request');
$search = new SearchDocsTool(new SimilarityRetrieval($store, $embeddings));
check(str_contains($search(query: 'refund days')->getText(), '[refund-policy.md]'),
    'search tool returns passages with sources');
check($search(query: 'Excel export')->getText()
    === 'No passage covers this question.', 'search tool applies the threshold');
$fake = new FakeAIProvider(new AssistantMessage('Hello!'));
$state = Agent::make()->setAiProvider($fake)->addTool($search)
    ->setThreadId('u')->chat(new UserMessage('Hello'));
check($fake->getRecorded()[0]->tools !== [] && $state->getToolRuns() === [],
    'agentic RAG offers the tool without forcing a search');
unlink($dbPath);
echo 'KNOWLEDGE OK' . PHP_EOL;
