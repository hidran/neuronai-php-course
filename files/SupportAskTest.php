<?php
declare(strict_types=1);
use App\Knowledge\SupportAgent;
use App\Support\Container;
use App\Support\SupportAsk;
use App\Support\SupportQuestion;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Testing\FakeEmbeddingsProvider;
use NeuronAI\Testing\FakeVectorStore;
use PHPUnit\Framework\TestCase;
final class SupportAskTest extends TestCase
{
    private function container(FakeAIProvider $fake): Container
    {
        $container = new Container();
        $container->bind(SupportAgent::class, fn () => new SupportAgent(
            $fake, new FakeEmbeddingsProvider(), new FakeVectorStore(),
            new PDO('sqlite::memory:'), 'C-118'));
        return $container;
    }
    public function testAnEmptyQuestionIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SupportQuestion::fromInput('   ');
    }
    public function testTheAnswerTextIsReturned(): void
    {
        $fake = new FakeAIProvider(new AssistantMessage('Five days.'));
        $ask = new SupportAsk($this->container($fake));
        $answer = $ask(SupportQuestion::fromInput('Refund time?'));
        self::assertSame('Five days.', $answer);
        self::assertSame(1, $fake->getCallCount());
    }
}
