<?php
declare(strict_types=1);
namespace App\Knowledge;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\RAG\PostProcessor\FixedThresholdPostProcessor;
use NeuronAI\RAG\Retrieval\RetrievalInterface;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
/** Agentic RAG: retrieval the model decides to call, as a tool. */
final class SearchDocsTool extends Tool
{
    protected string $name = 'search_docs';
    protected ?string $description = 'Searches the support docs'
        . ' and returns the most relevant passages with sources.'
        . ' Call it before answering a policy question.';
    public function __construct(
        private readonly RetrievalInterface $docs,
        private readonly float $threshold = 0.50,
    ) {
    }
    #[\Override]
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'query',
            type: PropertyType::STRING,
            description: 'What to look for, in plain words.',
            required: true,
        )];
    }
    public function __invoke(string $query): ToolOutput
    {
        $question = new UserMessage($query);
        $found = new FixedThresholdPostProcessor($this->threshold)
            ->process($question, $this->docs->retrieve($question));
        if ($found === []) {
            return ToolOutput::text('No passage covers this question.');
        }
        $lines = [];
        foreach ($found as $document) {
            $lines[] = "[{$document->getSourceName()}] "
                . trim($document->getContent());
        }
        return ToolOutput::text(implode("\n\n", $lines));
    }
}
