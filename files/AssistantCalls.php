<?php
declare(strict_types=1);
namespace App\Upgrade;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
use RuntimeException;
final readonly class AssistantCalls
{
    public function __construct(private Agent $agent)
    {
    }
    public function answer(string $question): string
    {
        $state = $this->agent->chat(new UserMessage($question));
        $message = $state->getMessage()
            ?? throw new RuntimeException('The agent sent no message.');
        return $message->getContent() ?? '';
    }
    public function streamed(string $question): StreamedAnswer
    {
        $stream = $this->agent->stream(new UserMessage($question));
        $text = '';
        $chunks = 0;
        foreach ($stream as $chunk) {
            if ($chunk instanceof TextChunk) {
                $text .= $chunk->content;
                $chunks++;
            }
        }
        return new StreamedAnswer($text, $chunks, $stream->getReturn());
    }
}
