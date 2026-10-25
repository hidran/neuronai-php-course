<?php
declare(strict_types=1);
namespace App\Config;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
/** Everything a RAG script needs, built once from the configuration. */
final readonly class RagServices
{
    public function __construct(
        public Settings $settings,
        public AIProviderInterface $provider,
        public EmbeddingsProviderInterface $embeddings,
        public VectorStoreFactory $stores,
    ) {
    }
}
