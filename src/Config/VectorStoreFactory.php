<?php
declare(strict_types=1);
namespace App\Config;
use NeuronAI\RAG\VectorStore\FileVectorStore;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use NeuronAI\RAG\VectorStore\VectorStoreInterface;
final readonly class VectorStoreFactory
{
    public function __construct(private VectorStoreSettings $settings)
    {
    }
    /** One collection per name: a separate file in the file store. */
    public function create(string $collection): VectorStoreInterface
    {
        return match ($this->settings->kind) {
            'file' => new FileVectorStore(
                directory: $this->settings->directory,
                topK: $this->settings->topK,
                name: $collection,
            ),
            default => new MemoryVectorStore(
                topK: $this->settings->topK),
        };
    }
}
