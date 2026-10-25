<?php
declare(strict_types=1);
namespace App\Config;
use NeuronAI\RAG\Embeddings\EmbeddingsProviderInterface;
use NeuronAI\RAG\Embeddings\GeminiEmbeddingsProvider;
use NeuronAI\RAG\Embeddings\OllamaEmbeddingsProvider;
use NeuronAI\RAG\Embeddings\OpenAIEmbeddingsProvider;
final readonly class EmbeddingsFactory
{
    private const string OLLAMA_URL = 'http://localhost:11434/api';
    public function __construct(private EmbeddingsSettings $settings)
    {
    }
    public function create(): EmbeddingsProviderInterface
    {
        $s = $this->settings;
        return match ($s->provider) {
            EmbeddingsProvider::OpenAI => new OpenAIEmbeddingsProvider(
                key: (string) $s->key,
                model: $s->model,
                dimensions: $s->dimensions,
            ),
            EmbeddingsProvider::Gemini => new GeminiEmbeddingsProvider(
                key: (string) $s->key,
                model: $s->model,
                config: $s->dimensions === null ? []
                    : ['outputDimensionality' => $s->dimensions],
            ),
            EmbeddingsProvider::Ollama => new OllamaEmbeddingsProvider(
                model: $s->model,
                url: $s->url ?? self::OLLAMA_URL,
            ),
        };
    }
}
