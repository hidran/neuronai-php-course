<?php
declare(strict_types=1);
namespace App\Upgrade;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
// The old Tool::make('word_count', ...) as a named class:
// same name, same description, same input contract.
final class WordCountTool extends Tool
{
    protected string $name = 'word_count';
    protected ?string $description = 'Count the words in a text.';
    #[\Override]
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'text',
            type: PropertyType::STRING,
            description: 'The text to count.',
            required: true,
        )];
    }
    public function __invoke(string $text): int
    {
        return str_word_count($text);
    }
}
