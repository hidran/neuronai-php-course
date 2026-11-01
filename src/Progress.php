<?php
declare(strict_types=1);
namespace App;
use NeuronAI\Tools\ToolCall;
final readonly class Progress
{
    private const array LABELS = [
        'read_composer_manifest' => 'manifest',
        'read_git_history' => 'history',
        'audit_composer_lock' => 'advisories',
        'glob_path' => 'file list',
        'read_file' => 'file read',
        'grep_file_content' => 'file search',
    ];
    /** @param resource $stream */
    public function __construct(private mixed $stream = STDERR)
    {
    }
    public function started(ToolCall $call): void
    {
        $this->line('> ' . $this->label($call) . ' started '
            . $this->id($call));
    }
    public function finished(ToolCall $call): void
    {
        $this->line('< ' . $this->label($call) . ' collected '
            . $this->id($call));
    }
    public function line(string $text): void
    {
        fwrite($this->stream, $text . PHP_EOL);
    }
    private function label(ToolCall $call): string
    {
        return self::LABELS[$call->getName()] ?? 'other tool';
    }
    private function id(ToolCall $call): string
    {
        return '#' . substr($call->getCallId() ?? 'none', -6);
    }
}
