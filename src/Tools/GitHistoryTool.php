<?php
declare(strict_types=1);
namespace App\Tools;
use App\Evidence\EvidenceLog;
use App\Evidence\GitLogParser;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolProperty;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
final class GitHistoryTool extends Tool
{
    public const int MAX_COMMITS = 100;
    protected string $name = 'read_git_history';
    protected ?string $description = 'Recent commits: id, date, '
        . 'author display name and subject. Bounded sample.';
    public function __construct(
        private readonly string $root,
        private readonly EvidenceLog $log,
        private readonly string $git = 'git',
    ) {
    }
    #[\Override]
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'days',
            type: PropertyType::INTEGER,
            description: 'How many days back, from 1 to 365.',
            required: true,
        )];
    }
    public static function clamp(int $days): int
    {
        return max(1, min(365, $days));
    }
    public function command(int $days): array
    {
        $days = self::clamp($days);
        return [$this->git, '--no-pager', 'log', '--no-color',
            '--no-decorate', '--max-count=' . (self::MAX_COMMITS + 1),
            "--since={$days}.days.ago",
            '--format=%h%x1f%as%x1f%an%x1f%s%x1e'];
    }
    public function __invoke(int $days): string
    {
        $days = self::clamp($days);
        if (!is_dir("{$this->root}/.git")) {
            return $this->log->unavailable('history',
                'the target has no git metadata');
        }
        $process = new Process($this->command($days), $this->root,
            timeout: 5);
        try {
            $process->run();
        } catch (RuntimeException) {
            return $this->log->unavailable('history', 'git timed out');
        }
        if (!$process->isSuccessful()) {
            return $this->log->unavailable('history', 'git log failed');
        }
        return $this->log->record('history', 'ok', GitLogParser::facts(
            $process->getOutput(), $days, self::MAX_COMMITS,
        ), "history-{$days}d");
    }
}
