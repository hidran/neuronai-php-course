<?php
declare(strict_types=1);
use App\Upgrade\AssistantCalls;
use App\Upgrade\NotesAgent;
use App\Upgrade\WordCountTool;
use NeuronAI\Agent\AgentState;
use NeuronAI\Agent\Observability\InferenceStart;
use NeuronAI\Chat\History\FileMessageStore;
use NeuronAI\Chat\History\InMemoryMessageStore;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use PHPUnit\Framework\TestCase;
final class UpgradeContractTest extends TestCase
{
    private function agent(FakeAIProvider $fake): NotesAgent
    {
        $store = new InMemoryMessageStore();
        return new NotesAgent($fake, $store)->for('upgrade-1');
    }
    public function testChatReturnsAgentState(): void
    {
        $fake = new FakeAIProvider(new AssistantMessage('A trait.'));
        $state = $this->agent($fake)->chat(new UserMessage('Hi'));
        self::assertInstanceOf(AgentState::class, $state);
    }
    public function testAnswerMapsTheMessageContent(): void
    {
        $fake = new FakeAIProvider(new AssistantMessage('A trait.'));
        $calls = new AssistantCalls($this->agent($fake));
        $answer = $calls->answer('What is a trait?');
        self::assertSame('A trait.', $answer);
        self::assertSame(1, $fake->getCallCount());
    }
    public function testStreamYieldsChunksAndReturnsState(): void
    {
        $fake = new FakeAIProvider(new AssistantMessage('An enum.'));
        $fake->setStreamChunkSize(3);
        $calls = new AssistantCalls($this->agent($fake));
        $answer = $calls->streamed('And an enum?');
        self::assertSame('An enum.', $answer->text);
        self::assertSame(3, $answer->textChunks);
        self::assertInstanceOf(AgentState::class, $answer->state);
    }
    public function testWordCountToolKeepsItsContract(): void
    {
        $tool = new WordCountTool();
        self::assertSame('word_count', $tool->getName());
        self::assertSame(3, $tool('one two three'));
    }
    public function testForReturnsABoundCopy(): void
    {
        $fake = new FakeAIProvider();
        $agent = new NotesAgent($fake, new InMemoryMessageStore());
        $bound = $agent->for('notes-42');
        self::assertNull($agent->getThreadId());
        self::assertSame('notes-42', $bound->getThreadId());
    }
    public function testStoredMessagesReachAFreshInstance(): void
    {
        $dir = sys_get_temp_dir() . '/upgrade-' . uniqid();
        mkdir($dir);
        $fake = new FakeAIProvider(new AssistantMessage('Noted.'));
        $store = new FileMessageStore($dir);
        new NotesAgent($fake, $store)->for('notes-42')
            ->chat(new UserMessage('We deploy on Friday.'));
        $fresh = new NotesAgent($fake, new FileMessageStore($dir))
            ->for('notes-42');
        $messages = $fresh->getChatHistory()->getMessages();
        self::assertCount(2, $messages);
    }
    public function testAnInstalledEventReachesTheListener(): void
    {
        $fake = new FakeAIProvider(new AssistantMessage('Yes.'));
        $seen = 0;
        $agent = $this->agent($fake)->subscribe(
            InferenceStart::class,
            function () use (&$seen): void { $seen++; },
        );
        $agent->chat(new UserMessage('Listening?'));
        self::assertSame(1, $seen);
    }
    public function testAnOldEventNameIsAcceptedButNeverFires(): void
    {
        $fake = new FakeAIProvider(new AssistantMessage('Yes.'));
        $seen = 0;
        $agent = $this->agent($fake)->subscribe(
            'NeuronAI\Observability\Events\InferenceStart',
            function () use (&$seen): void { $seen++; },
        );
        $agent->chat(new UserMessage('Listening?'));
        self::assertSame(0, $seen);
    }
}
