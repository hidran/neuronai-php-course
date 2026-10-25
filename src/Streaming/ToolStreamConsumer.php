<?php
declare(strict_types=1);
namespace App\Streaming;
use Generator;
use NeuronAI\Agent\AgentState;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolArgumentChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolCallChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolResultChunk;
use NeuronAI\Exceptions\NeuronException;
final readonly class ToolStreamConsumer
{
    public function __construct(private StreamProgress $view)
    {
    }
    public function consume(Generator $stream): ?AgentState
    {
        try {
            foreach ($stream as $chunk) {
                $this->render($chunk);
            }
        } catch (NeuronException $error) {
            echo "\nThe answer did not finish: " . $error->getMessage()
                . PHP_EOL;
            return null;
        }
        $state = $stream->getReturn();
        $this->view->finish($state);
        return $state;
    }
    private function render(object $chunk): void
    {
        match (true) {
            $chunk instanceof ToolArgumentChunk
                => $this->view->arguments($chunk),
            $chunk instanceof ToolCallChunk
                => $this->view->call($chunk->tool),
            $chunk instanceof ToolResultChunk
                => $this->view->result($chunk->tool),
            $chunk instanceof TextChunk
                => $this->view->text($chunk->content),
            default => null,
        };
    }
}
