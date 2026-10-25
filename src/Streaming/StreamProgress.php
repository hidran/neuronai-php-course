<?php
declare(strict_types=1);
namespace App\Streaming;
use NeuronAI\Agent\AgentState;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolArgumentChunk;
use NeuronAI\Tools\ToolCall;
final class StreamProgress
{
    private const array LABELS = [
        'get_server_configuration' => 'Checking server information',
    ];
    /** @var array<string, string> */
    private array $pending = [];
    /** @var array<string, string> */
    private array $completed = [];
    private bool $previewOpen = false;
    public function __construct(
        private readonly bool $diagnostic = true,
    ) {
    }
    public function arguments(ToolArgumentChunk $chunk): void
    {
        if (!$this->diagnostic) {
            return;
        }
        if (!$this->previewOpen) {
            echo "\nArguments so far: ";
            $this->previewOpen = true;
        }
        echo $chunk->delta;
        flush();
    }
    public function call(ToolCall $tool): void
    {
        $id = $tool->getCallId() ?? 'no-id';
        $this->pending[$id] = $tool->getName();
        $this->previewOpen = false;
        if ($this->diagnostic) {
            echo "\nCall {$id}: " . $tool->getName() . PHP_EOL;
            echo 'Inputs: ' . json_encode($tool->getInputs()) . PHP_EOL;
        } else {
            $label = self::LABELS[$tool->getName()]
                ?? 'Working on your request';
            echo "\n{$label}..." . PHP_EOL;
        }
        flush();
    }
    public function result(ToolCall $tool): void
    {
        $id = $tool->getCallId() ?? 'no-id';
        unset($this->pending[$id]);
        $this->completed[$id] = $tool->getName();
        if ($this->diagnostic) {
            echo "Done {$id}: " . $tool->getName() . PHP_EOL;
            echo 'Result: ' . $tool->getResult() . PHP_EOL;
        } else {
            echo 'Information ready.' . PHP_EOL;
        }
        flush();
    }
    public function text(string $content): void
    {
        echo $content;
        flush();
    }
    public function finish(AgentState $state): void
    {
        echo PHP_EOL . 'Tool runs: ' . array_sum($state->getToolRuns())
            . ', pending: ' . count($this->pending) . PHP_EOL;
        echo 'Tool stream complete.' . PHP_EOL;
    }
    /** @return array<string, string> */
    public function completed(): array
    {
        return $this->completed;
    }
}
