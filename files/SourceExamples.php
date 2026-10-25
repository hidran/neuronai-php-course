<?php
declare(strict_types=1);
namespace App;
use App\Agents\SourceAgent;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\Anthropic\MessageMapper;
use RuntimeException;
final readonly class SourceExamples
{
    public const string PUBLIC_IMAGE =
        'https://example.com/diagram.png';
    public function __construct(private SourceAgent $agent)
    {
    }
    public static function encoded(string $path): string
    {
        $bytes = is_file($path) ? file_get_contents($path) : false;
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException("Document read failed: {$path}");
        }
        return base64_encode($bytes);
    }
    public static function uploadedId(string $path): string
    {
        $bytes = is_file($path) ? file_get_contents($path) : false;
        if ($bytes === false) {
            throw new RuntimeException('Run the IDE upload first.');
        }
        $upload = json_decode($bytes, associative: true,
            flags: JSON_THROW_ON_ERROR);
        $id = $upload['id'] ?? '';
        if (!is_string($id) || !str_starts_with($id, 'file_')) {
            throw new RuntimeException('Upload ID required.');
        }
        return $id;
    }
    /** @param list<FileContent|ImageContent> $blocks */
    public static function summary(array $blocks): void
    {
        foreach ($blocks as $block) {
            echo $block::class . ' / ' . $block->sourceType->name
                . ' / content bytes: ' . strlen($block->content)
                . PHP_EOL;
        }
    }
    public static function mappedBytes(UserMessage $message): int
    {
        $payload = new MessageMapper()->map([$message]);
        return strlen(json_encode($payload, JSON_THROW_ON_ERROR));
    }
    public function ask(FileContent $block, string $expected): void
    {
        $message = new UserMessage([
            new TextContent('What is the invoice review code?'),
            $block,
        ]);
        $kind = $block->sourceType->name;
        echo $kind . ' mapped bytes: ' . self::mappedBytes($message)
            . PHP_EOL;
        $state = $this->agent->setThreadId(uniqid('source-', true))
            ->chat($message);
        $reply = $state->getMessage()?->getContent() ?? '';
        echo $kind . ' answer: ' . $reply . PHP_EOL;
        if (!str_contains($reply, $expected)) {
            throw new RuntimeException('Document fact missing.');
        }
        $tokens = $state->getMessage()?->getUsage()?->inputTokens;
        echo 'Input tokens: ' . ($tokens ?? 'unreported') . PHP_EOL;
        echo $kind . ' document verified.' . PHP_EOL;
    }
}
