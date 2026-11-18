<?php
declare(strict_types=1);
namespace App;
use App\Agents\AuditorAgent;
use App\Agents\ReportFormatter;
use App\Evidence\EvidenceLog;
use App\Evidence\ScoreRubric;
use App\Report\AuditReport;
use App\Report\ReportGate;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolCallChunk;
use NeuronAI\Chat\Messages\Stream\Chunks\ToolResultChunk;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Workflow\WorkflowStatus;
final readonly class AuditRunner
{
    private const array REQUIRED = ['structure', 'manifest',
        'history', 'advisories'];
    public function __construct(
        private AuditorAgent $collector,
        private ReportFormatter $formatter,
        private EvidenceLog $log,
        private Progress $progress,
        private RunGuard $guard,
    ) {
    }
    public function collect(): void
    {
        $stream = $this->collector->stream(
            new UserMessage('Collect the evidence for this audit.'),
        );
        foreach ($stream as $chunk) {
            if ($this->guard->stopped()) {
                break;
            }
            match ($chunk::class) {
                ToolCallChunk::class
                    => $this->progress->started($chunk->tool),
                ToolResultChunk::class
                    => $this->progress->finished($chunk->tool),
                TextChunk::class => null,
                default => null,
            };
        }
        if ($this->guard->stopped()) {
            unset($stream);
            $this->guard->check();
        }
        $state = $stream->getReturn();
        if ($state->isInterrupted()
            || $state->getStatus() !== WorkflowStatus::Completed) {
            throw new AuditFailed('the collector did not complete');
        }
        $missing = array_diff(self::REQUIRED, $this->log->checks());
        if ($missing !== []) {
            throw new AuditFailed('missing evidence: '
                . implode(', ', $missing));
        }
    }
    public function format(): AuditReport
    {
        $rubric = ScoreRubric::evaluate($this->log);
        $input = json_encode([
            'evidence' => array_values($this->log->all()),
            'rubric' => $rubric,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $report = $this->formatter->structured(
            new UserMessage($input), AuditReport::class, maxRetries: 2,
        );
        $problems = ReportGate::problems($report, $this->log, $rubric);
        if ($problems !== []) {
            throw new AuditFailed('report rejected: '
                . implode('; ', $problems));
        }
        return $report;
    }
}
