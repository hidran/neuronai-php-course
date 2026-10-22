<?php
declare(strict_types=1);
namespace App\Streaming;
use NeuronAI\Agent\AgentState;
use NeuronAI\Tools\ToolCall;
use RuntimeException;
class StreamProgress
{
    private const LABELS = [
        'get_server_configuration' => 'Checking server information',
    ];
    private array $pending = [];
    private array $completed = [];
    private string $text = '';
    public function __construct(private bool $diagnostic = true)
    {
    }
    public function call(ToolCall $tool): void
    {
        $id = $tool->getCallId();
        if ($id === null || $id === '') {
            throw new RuntimeException('Invalid call identifier.');
        }
        if (isset($this->pending[$id]) || isset($this->completed[$id])) {
            throw new RuntimeException('Duplicate call identifier.');
        }
        $this->pending[$id] = $tool->getName();
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
        $id = $tool->getCallId();
        if (!$id || ($this->pending[$id] ?? null) !== $tool->getName()) {
            throw new RuntimeException('Unmatched tool result.');
        }
        if (!$tool->hasResult()) {
            throw new RuntimeException('Tool result is unavailable.');
        }
        unset($this->pending[$id]);
        $this->text = ''; // Final reply starts after the tool round.
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
        $this->text .= $content;
        echo $content;
        flush();
    }
    public function finish(AgentState $state): void
    {
        if ($this->pending || !$this->completed || $this->text === '') {
            throw new RuntimeException('Incomplete text or tool lifecycle.');
        }
        if ($this->text !== $state->getMessage()?->getContent()) {
            throw new RuntimeException('Final text mismatch.');
        }
        if (count($this->completed) !== array_sum($state->getToolRuns())) {
            throw new RuntimeException('Tool execution count mismatch.');
        }
    }
    public function completed(): array
    {
        return $this->completed;
    }
}
