<?php
declare(strict_types=1);
namespace App\Streaming;
use Generator;
use NeuronAI\Agent\AgentState;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolCallChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolResultChunk;
use Throwable;
class ToolStreamConsumer
{
    public static function consume(
        Generator $stream,
        StreamProgress $view,
    ): ?AgentState {
        try {
            foreach ($stream as $chunk) {
                if ($chunk instanceof ToolCallChunk) {
                    $view->call($chunk->tool);
                    continue;
                }
                if ($chunk instanceof ToolResultChunk) {
                    $view->result($chunk->tool);
                    continue;
                }
                if ($chunk instanceof TextChunk) {
                    $view->text($chunk->content);
                }
            }
            $state = $stream->getReturn();
            $view->finish($state);
            echo "\nTool stream " . 'complete.' . PHP_EOL;
            return $state;
        } catch (Throwable) {
            echo "\nThe answer did not finish." . PHP_EOL;
            return null;
        }
    }
}
