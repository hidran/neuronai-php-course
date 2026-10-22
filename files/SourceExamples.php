<?php
declare(strict_types=1);
namespace App;
use App\Agents\SourceAgent;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Providers\OpenAI\MessageMapper;
use RuntimeException;
class SourceExamples
{
    public const PUBLIC_IMAGE = 'https://raw.githubusercontent.com/'
        . 'github/explore/main/topics/php/php.png';
    public static function encoded(string $path): string
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('Document read failed.');
        }
        return base64_encode($bytes);
    }
    public static function uploadedId(string $path): string
    {
        $bytes = @file_get_contents($path);
        if ($bytes === false) {
            throw new RuntimeException('Run the IDE upload request first.');
        }
        $upload = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
        $id = $upload['id'] ?? '';
        if (!is_string($id) || !str_starts_with($id, 'file-')) {
            throw new RuntimeException('Successful upload ID required.');
        }
        return $id;
    }
    public static function summary(array $blocks): void
    {
        foreach ($blocks as $block) {
            echo $block::class . ' / ' . $block->sourceType->name
                . ' / content bytes: ' . strlen($block->content)
                . PHP_EOL;
        }
    }
    public static function ask(FileContent $block, string $expected): void
    {
        $message = new UserMessage([
            new TextContent('What is the invoice review code?'),
            $block,
        ]);
        $payload = (new MessageMapper())->map([$message]);
        $size = strlen(json_encode($payload, JSON_THROW_ON_ERROR));
        echo $block->sourceType->name . ' mapped bytes: ' . $size
            . PHP_EOL;
        $state = SourceAgent::make()->setThreadId(uniqid('source-', true))
            ->chat($message);
        $reply = $state->getMessage()?->getContent() ?? '';
        echo $block->sourceType->name . ' answer: ' . $reply . PHP_EOL;
        if (!str_contains($reply, $expected)) {
            throw new RuntimeException('Document fact missing.');
        }
        $tokens = $state->getMessage()?->getUsage()?->inputTokens;
        echo 'Input tokens: ' . ($tokens ?? 'unreported') . PHP_EOL;
        echo $block->sourceType->name . ' document verified.' . PHP_EOL;
    }
}
