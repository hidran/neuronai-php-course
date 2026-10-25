<?php
declare(strict_types=1);
namespace App\Rag;
use NeuronAI\RAG\Embeddings\AbstractEmbeddingsProvider;
/**
 * A test double: counts a fixed word list, no network. The same text
 * always gives the same vector, so retrieval checks stay deterministic.
 */
final class KeywordEmbeddings extends AbstractEmbeddingsProvider
{
    private const array WORDS = ['support', 'chat', 'hours', 'locked',
        'account', 'invoice', 'month', 'refund', 'card', 'release',
        'credit', 'expire', 'ship', 'warehouse', 'damaged', 'photo',
        'retry', 'limit', 'plan', 'return', 'setup', 'settings', 'sync',
        'config', 'schedule', 'install', 'subscription', 'guide'];
    #[\Override]
    public function embedText(string $text): array
    {
        $text = strtolower($text);
        $vector = [0.01];
        foreach (self::WORDS as $word) {
            $vector[] = (float) substr_count($text, $word);
        }
        return $vector;
    }
}
