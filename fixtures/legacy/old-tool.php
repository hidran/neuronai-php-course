<?php
// An older tutorial snippet, kept as a fixture: never run it.
declare(strict_types=1);
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
$tool = Tool::make('word_count', 'Count the words in a text.')
    ->addProperty(new ToolProperty(
        name: 'text',
        type: PropertyType::STRING,
        description: 'The text to count.',
        required: true,
    ))
    ->setCallable(fn (string $text): int => str_word_count($text));
