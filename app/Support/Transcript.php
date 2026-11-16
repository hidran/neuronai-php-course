<?php
declare(strict_types=1);
namespace App\Support;
use App\Models\ChatMessage;
use App\Models\Conversation;
final readonly class Transcript
{
    /** @return list<TranscriptLine> */
    public function for(Conversation $conversation): array
    {
        return ChatMessage::query()
            ->where('thread_id', $conversation->threadId())
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('id')
            ->get()
            ->map(fn (ChatMessage $row) => new TranscriptLine(
                role: $row->role,
                text: $this->text($row->content ?? []),
            ))
            ->reject(fn (TranscriptLine $line) => $line->text === '')
            ->values()
            ->all();
    }
    /** @param list<array{type?: string, content?: mixed}> $blocks */
    private function text(array $blocks): string
    {
        $texts = [];
        foreach ($blocks as $block) {
            if (($block['type'] ?? null) === 'text') {
                $texts[] = (string) ($block['content'] ?? '');
            }
        }
        return implode("\n", $texts);
    }
}
