<?php
declare(strict_types=1);
namespace App\Support;
use App\Config\StorageSettings;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\History\SQLMessageStore;
use RuntimeException;
final readonly class MessageStoreFactory
{
    public function __construct(
        private StorageSettings $settings,
        private Database $database,
    ) {
    }
    #[\NoDiscard]
    public function create(): MessageStoreInterface
    {
        $s = $this->settings;
        return match ($s->chatStore) {
            'memory' => new InMemoryMessageStore(),
            'file' => new FileMessageStore($s->path, ext: '.json'),
            'sqlite' => $this->sqlStore(),
            default => throw new RuntimeException(
                "Unknown CHAT_STORE {$s->chatStore}"
            ),
        };
    }
    private function sqlStore(): SQLMessageStore
    {
        $sql = dirname(__DIR__, 2) . '/database/chat_messages.sql';
        $this->database->migrate($sql);
        return new SQLMessageStore($this->database->pdo());
    }
}
