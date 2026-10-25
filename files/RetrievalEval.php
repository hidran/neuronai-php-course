<?php
declare(strict_types=1);
namespace App\Rag;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\Retrieval\RetrievalInterface;
/**
 * Scores a retrieval setup on labeled questions: did the expected
 * source
 * come first (hit), and did an unanswerable question return nothing?
 */
final readonly class RetrievalEval
{
    /** @param array<string, array{string, ?string}> $questions */
    public function __construct(private array $questions)
    {
    }
    /**
     * @return array{hits: int, total: int, refused: int,
     *     rows: list<string>}
     */
    public function run(RetrievalInterface $retrieval): array
    {
        $hits = 0;
        $refused = 0;
        $answerable = 0;
        $rows = [];
        foreach ($this->questions as $label => [$question, $expected]) {
            $found = $retrieval->retrieve(new UserMessage($question));
            $top = $found[0] ?? null;
            $topName = $top === null ? '(nothing)'
                : basename($top->getSourceName());
            if ($expected === null) {
                $refused += $top === null ? 1 : 0;
                $rows[] = sprintf('%-12s expected nothing, got %s',
                    $label, $topName);
                continue;
            }
            $answerable++;
            $hit = $topName === $expected;
            $hits += $hit ? 1 : 0;
            $rows[] = sprintf('%-12s %s expected %s, got %s (%s)',
                $label,
                $hit ? 'hit ' : 'miss', $expected, $topName,
                $top === null ? 'no score'
                    : sprintf('%.2f', $top->getScore()));
        }
        return ['hits' => $hits, 'total' => $answerable,
            'refused' => $refused, 'rows' => $rows];
    }
}
