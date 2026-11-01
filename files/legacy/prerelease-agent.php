<?php
// A prerelease snippet, kept as a fixture: never run it.
declare(strict_types=1);
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\History\ChatHistoryInterface;
use NeuronAI\Chat\History\FileChatHistory;
use NeuronAI\Chat\Messages\UserMessage;
final class NotesAgent extends Agent
{
    protected function chatHistory(): ChatHistoryInterface
    {
        return new FileChatHistory(__DIR__ . '/notes', 'team');
    }
}
$agent = NotesAgent::make(threadId: 'notes-42');
$agent->chat(new UserMessage('Remember: we deploy on Friday.'));
