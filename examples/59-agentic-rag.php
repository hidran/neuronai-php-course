<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Knowledge\SearchDocsTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\Retrieval\SimilarityRetrieval;
$rag = require __DIR__ . '/rag-bootstrap.php';
$store = $rag->stores->create('support-policy');
$search = new SearchDocsTool(new SimilarityRetrieval($store,
    $rag->embeddings));
$agent = Agent::make()->setAiProvider($rag->provider)
    ->setInstructions(new SystemMessage('Answer support questions. '
        . 'Search the documentation only when the question is about '
        . 'a policy; greet without searching.'))
    ->addTool($search)->setThreadId('agentic-rag');
foreach (['Hello, are you there?',
    'How many days do refunds take to reach my card?'] as $question) {
    $state = $agent->chat(new UserMessage($question));
    echo "Q: {$question}" . PHP_EOL;
    echo 'A: ' . $state->getMessage()?->getContent() . PHP_EOL;
    echo 'search_docs calls: ' . $state->getToolRuns('search_docs')
        . PHP_EOL;
}
echo 'Agentic RAG run complete.' . PHP_EOL;
