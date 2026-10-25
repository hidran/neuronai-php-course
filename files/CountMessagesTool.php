<?php
declare(strict_types=1);
namespace App\Tools;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
use PDO;
final class CountMessagesTool extends Tool
{
    protected string $name = 'count_messages';
    protected ?string $description = 'Counts the messages our assistant'
        . ' stored in SQLite for one chat thread.';
    public function __construct(private readonly PDO $pdo)
    {
    }
    #[\Override]
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
