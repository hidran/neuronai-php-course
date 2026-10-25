<?php
declare(strict_types=1);
namespace App\Knowledge;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
use PDO;
final class SupportAgent extends RAG
{
    public function __construct(
        AIProviderInterface $provider,
        EmbeddingsProviderInterface $embeddings,
        VectorStoreInterface $store,
        PDO $orders,
        string $customerId,
    ) {
        parent::__construct();
        $this->setAiProvider($provider);
        $this->setEmbeddingsProvider($embeddings);
        $this->setVectorStore($store);
        $this->addTool(new OrderStatusTool($orders, $customerId));
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return new SystemMessage((string) new SystemPrompt(
            background: ['You write for Cedar Support: warm and brief.'],
            steps: [
                'Take policy rules only from the supplied passages.',
                'Take order facts only from the order_status tool.',
            ],
            output: ['Never guess an order status.',
                'Use at most three sentences.'],
        ));
    }
}
