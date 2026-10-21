<?php

declare(strict_types=1);

namespace App\Tools;

use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
use PDO;

class CountMessagesTool extends Tool
{
    protected string $name = 'count_messages';
    protected ?string $description = 'Counts the messages our assistant'
        . ' stored in SQLite for one chat thread.';

    public function __construct(private PDO $pdo)
    {
    }

    /**
     * Properties are the input arguments of the __invoke method.
     */
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'thread',
                type: PropertyType::STRING,
                description: 'The thread ID, like bob.',
                required: true,
            ),
        ];
    }

    /**
     * Implementing the tool logic
     */
    public function __invoke(string $thread): ToolOutput
    {
        $query = $this->pdo->prepare(
            'SELECT COUNT(*) FROM chat_messages WHERE thread_id = ?'
        );
        $query->execute([$thread]);
        $count = (int) $query->fetchColumn();
        return ToolOutput::text(
            "Stored messages on thread {$thread}: {$count}."
        );
    }
}
