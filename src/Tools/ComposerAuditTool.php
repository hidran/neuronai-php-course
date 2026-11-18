<?php
declare(strict_types=1);
namespace App\Tools;
use App\Evidence\AdvisoryNormalizer;
use App\Evidence\EvidenceLog;
use NeuronAI\Tools\Tool;
use Symfony\Component\Process\Exception\RuntimeException;
use Symfony\Component\Process\Process;
final class ComposerAuditTool extends Tool
{
    protected string $name = 'audit_composer_lock';
    protected ?string $description = 'Check the locked packages for '
        . 'security advisories and abandoned packages.';
    public function __construct(
        private readonly string $root,
        private readonly EvidenceLog $log,
        private readonly string $composer = 'composer',
        private readonly float $timeout = 30.0,
    ) {
    }
    public function __invoke(): string
    {
        if (!is_readable("{$this->root}/composer.lock")) {
            return $this->log->unavailable('advisories',
                'no readable composer.lock, nothing locked to audit');
        }
        $process = new Process([$this->composer, '--no-plugins',
            '--no-scripts', '--no-interaction', 'audit', '--locked',
            '--format=json'], $this->root, timeout: $this->timeout);
        $this->log->begin('advisories');
        try {
            $process->run();
        } catch (RuntimeException) {
            return $this->log->unavailable('advisories',
                'composer audit timed out or could not start');
        }
        $result = new AdvisoryNormalizer()->normalize(
            $process->getOutput(), $process->getExitCode());
        if ($result === null) {
            return $this->log->unavailable('advisories',
                'composer audit returned no usable report');
        }
        return $this->log->record('advisories', $result['status'],
            $result['facts']);
    }
}
