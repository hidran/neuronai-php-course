<?php
declare(strict_types=1);
namespace App\Streaming;
use Generator;
use NeuronAI\Agent\AgentState;
// Import the three chunk types here.
use Throwable;
class ToolStreamConsumer
{
    public static function consume(
        Generator $stream,
        StreamProgress $view,
    ): ?AgentState {
        try {
            foreach ($stream as $chunk) {
                // Handle complete calls, results and text here.
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
