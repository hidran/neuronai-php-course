<?php
declare(strict_types=1);
namespace App\Support;
use App\Knowledge\SupportAgent;
use NeuronAI\Chat\Messages\UserMessage;
use RuntimeException;
final readonly class SupportAsk
{
    public function __construct(private Container $container)
    {
    }
    public function __invoke(SupportQuestion $question): string
    {
        // The server chooses the thread: a new one for every question.
        $thread = 'support-' . bin2hex(random_bytes(8));
        $agent = $this->container->get(SupportAgent::class)
            ->for($thread);
        $state = $agent->chat(new UserMessage($question->text));
        $message = $state->getMessage()
            ?? throw new RuntimeException('The agent sent no answer.');
        return $message->getContent() ?? '';
    }
}
