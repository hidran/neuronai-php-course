<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Knowledge\OrderDatabase;
use App\Knowledge\SupportAgent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\DataLoader\FileDataLoader;
$rag = require __DIR__ . '/rag-bootstrap.php';
$root = dirname(__DIR__);
$customer = 'C-118';
$question = 'Was order 4471 refunded, and when will I see the money?';
$orders = new OrderDatabase(
    $root . '/storage/knowledge/orders.sqlite');
$agent = SupportAgent::make($rag->provider, $rag->embeddings,
    $rag->stores->create('support-policy'), $orders->pdo(), $customer)
    ->setThreadId('support-' . $customer);
$policy = FileDataLoader::for(
    $root . '/fixtures/knowledge/refund-policy.md')->getDocuments();
foreach ($policy as $document) {
    $document->setSourceName(basename($document->getSourceName()));
}
$agent->reindexBySource($policy);
$state = $agent->chat(new UserMessage($question));
echo "Customer {$customer}: {$question}" . PHP_EOL;
echo $state->getMessage()?->getContent() . PHP_EOL;
$context = implode(' ', array_map(
    static fn ($block) => $block->getContent(),
    $state->request->context));
$used = str_contains($context, 'refund-policy.md') ? 'yes' : 'no';
echo "Policy passage in context: {$used}" . PHP_EOL;
echo 'Order tool calls: ' . $state->getToolRuns('order_status') . PHP_EOL;
echo 'Combined support run complete.' . PHP_EOL;
