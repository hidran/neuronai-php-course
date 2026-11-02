<?php
declare(strict_types=1);
namespace App\Neuron\Agents;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\RAG;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
final class SupportDocsAgent extends RAG
{
    public function __construct(
        AIProviderInterface $provider,
        EmbeddingsProviderInterface $embeddings,
        VectorStoreInterface $store,
    ) {
        parent::__construct();
        $this->setAiProvider($provider);
        $this->setEmbeddingsProvider($embeddings);
        $this->setVectorStore($store);
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return new SystemMessage((string) new SystemPrompt(
            background: ['You answer from the support docs.'],
            steps: ['Use only the retrieved passages.'],
            output: ['If the passages do not answer, say so.'],
        ));
    }
}
