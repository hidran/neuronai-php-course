<?php
declare(strict_types=1);
namespace App\Observability;
use NeuronAI\Agent\Agent;
use RuntimeException;
final readonly class TraceReport
{
    /** @param array<string, mixed> $data */
    public function __construct(public array $data)
    {
    }
    public static function fromRun(string $label, string $question,
        Agent $agent, TraceRecorder $recorder, ?string $answer): self
    {
        return new self([
            'label' => $label,
            'utc' => gmdate(DATE_ATOM),
            'question' => $question,
            'fixture' => 'Teaching values, not live weather',
            'offered' => array_map(static fn ($tool): array => [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'schema' => $tool->getInputSchema(),
            ], $agent->getTools()),
            'inferences' => $recorder->inferences(),
            'tools' => $recorder->tools(),
            'cost_usd' => array_sum(array_column(
                $recorder->inferences(), 'cost_usd')),
            'wall_ms' => $recorder->wallMilliseconds(),
            'answer' => $answer,
        ]);
    }
    public static function load(string $path): self
    {
        $json = file_get_contents($path)
            ?: throw new RuntimeException("No saved trace at {$path}");
        return new self(json_decode($json, associative: true,
            flags: JSON_THROW_ON_ERROR));
    }
    public function save(string $path): void
    {
        file_put_contents($path, json_encode($this->data,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL);
    }
    public function show(): void
    {
        echo 'Run label: ' . $this->data['label'] . PHP_EOL;
        echo 'Inference requests: ' . count($this->data['inferences'])
            . PHP_EOL;
        foreach ($this->data['inferences'] as $index => $inference) {
            echo 'Inference ' . ($index + 1) . ': '
                . json_encode($inference) . PHP_EOL;
        }
        foreach ($this->data['tools'] as $tool) {
            echo 'Completed tool: ' . json_encode($tool) . PHP_EOL;
        }
        echo 'Wall milliseconds: ' . round($this->data['wall_ms'], 2)
            . PHP_EOL;
        echo 'Estimated cost USD: ' . $this->data['cost_usd'] . PHP_EOL;
        echo 'Answer: ' . $this->data['answer'] . PHP_EOL;
        echo 'Trace analysis complete.' . PHP_EOL;
    }
}
