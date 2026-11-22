<?php

declare(strict_types=1);
// Reference for the closing exercise: one more narrow tool for the repo auditor, read_composer_licenses.
// Usage: php exercise-license-tool.php <repo-auditor project>. Offline: the tool reads the fixtures' composer.lock
// files, a probe agent calls it through FakeAIProvider, and the gate shows that its evidence must be reported.
// In the project the class lives in src/Tools/ComposerLicenseTool.php; it is defined here so this file runs alone.

namespace {
    $project = rtrim($argv[1] ?? throw new RuntimeException('project path'), '/');
    require $project . '/vendor/autoload.php';
}

namespace App\Tools {
    use App\Evidence\EvidenceLog;
    use NeuronAI\Tools\Tool;

    final class ComposerLicenseTool extends Tool
    {
        protected string $name = 'read_composer_licenses';
        protected ?string $description = 'Count the licenses of the '
            . 'locked packages and list packages without one.';

        public function __construct(
            private readonly string $root,
            private readonly EvidenceLog $log,
        ) {
        }

        public function __invoke(): string
        {
            $file = "{$this->root}/composer.lock";
            if (!is_file($file) || !is_readable($file)) {
                return $this->log->unavailable(
                    'licenses',
                    'no readable composer.lock',
                );
            }
            $lock = json_decode((string) file_get_contents($file), true);
            if (!is_array($lock)) {
                return $this->log->unavailable(
                    'licenses',
                    'composer.lock is not valid JSON',
                );
            }
            $packages = [...$lock['packages'] ?? [],
                ...$lock['packages-dev'] ?? []];
            $counts = [];
            $unlicensed = [];
            foreach ($packages as $package) {
                $licenses = $package['license'] ?? [];
                if ($licenses === []) {
                    $unlicensed[] = (string) ($package['name'] ?? '?');
                }
                foreach ($licenses as $license) {
                    $counts[$license] = ($counts[$license] ?? 0) + 1;
                }
            }
            ksort($counts);
            return $this->log->record('licenses', 'ok', [
                'packages' => count($packages),
                'licenses' => $counts,
                'unlicensed' => array_slice($unlicensed, 0, 20),
            ]);
        }
    }
}

namespace {
    use App\Evidence\EvidenceLog;
    use App\Report\AuditReport;
    use App\Report\CheckStatus;
    use App\Report\ReportGate;
    use App\Tools\ComposerLicenseTool;
    use NeuronAI\Agent\Agent;
    use NeuronAI\Chat\Messages\AssistantMessage;
    use NeuronAI\Chat\Messages\ToolCallMessage;
    use NeuronAI\Chat\Messages\UserMessage;
    use NeuronAI\Providers\AIProviderInterface;
    use NeuronAI\Testing\FakeAIProvider;
    use NeuronAI\Tools\ToolCall;

    $fail = static fn(string $why): never => throw new RuntimeException($why);
    $facts = static function (string $fixture) use ($project): array {
        $log = new EvidenceLog();
        new ComposerLicenseTool("{$project}/fixtures/{$fixture}", $log)();
        return $log->get('licenses') ?? [];
    };
    // 1. The fixtures: counts per license id, unavailable without a lock.
    $library = $facts('library');
    if ($library['status'] !== 'ok' || $library['facts']['packages'] !== 28
        || $library['facts']['licenses'] !== ['BSD-3-Clause' => 25, 'MIT' => 3]
        || $library['facts']['unlicensed'] !== []) {
        $fail('library licenses: ' . json_encode($library));
    }
    if ($facts('app')['facts']['licenses'] !== ['MIT' => 4]) {
        $fail('app licenses');
    }
    if ($facts('no-history') !== ['id' => 'licenses', 'check' => 'licenses', 'status' => 'unavailable',
        'reason' => 'no readable composer.lock']) {
        $fail('a project without a lock must be unavailable');
    }
    // 2. A package without a license is listed by name.
    $tmp = sys_get_temp_dir() . '/exercise-license-' . getmypid();
    mkdir($tmp);
    file_put_contents("{$tmp}/composer.lock", json_encode(['packages' => [
        ['name' => 'acme/open', 'license' => ['MIT']], ['name' => 'acme/mystery'],
    ]]));
    $log = new EvidenceLog();
    new ComposerLicenseTool($tmp, $log)();
    unlink("{$tmp}/composer.lock");
    rmdir($tmp);
    if ($log->get('licenses')['facts']['unlicensed'] !== ['acme/mystery']) {
        $fail('unlicensed package not listed');
    }
    // 3. Through an agent loop: the model asks for the tool by name, the tool records its evidence.
    $probeLog = new EvidenceLog();
    $probe = new class (new FakeAIProvider(
        new ToolCallMessage(null, [new ToolCall('read_composer_licenses', 'call_licenses', [])]),
        new AssistantMessage('Licenses collected.'),
    ), new ComposerLicenseTool("{$project}/fixtures/library", $probeLog)) extends Agent {
        public function __construct(AIProviderInterface $provider, private readonly ComposerLicenseTool $tool)
        {
            parent::__construct();
            $this->setAiProvider($provider);
        }

        #[\Override]
        protected function tools(): array
        {
            return [$this->tool->setMaxRuns(1)];
        }
    };
    $probe->setThreadId('licenses-probe')->chat(new UserMessage('Collect the licenses.'));
    if ($probeLog->checks() !== ['licenses']) {
        $fail('the agent did not run the license tool');
    }
    // 4. The gate: once the evidence exists, a report without a licenses check is rejected.
    $checks = [new CheckStatus('structure', 'ok', 'structure')];
    $gateLog = new EvidenceLog();
    $gateLog->record('structure', 'ok', []);
    new ComposerLicenseTool("{$project}/fixtures/library", $gateLog)();
    $rubric = ['score' => 100, 'penalties' => [], 'unavailable' => []];
    $without = ReportGate::problems(
        new AuditReport('Licenses counted, nothing else.', 100, [], $checks),
        $gateLog,
        $rubric,
    );
    $with = ReportGate::problems(new AuditReport(
        'Licenses counted, nothing else.',
        100,
        [],
        [...$checks, new CheckStatus('licenses', 'ok', 'licenses')],
    ), $gateLog, $rubric);
    if ($without !== ['check licenses must be ok'] || $with !== []) {
        $fail('gate: ' . json_encode([$without, $with]));
    }
    echo 'Library: 28 packages, BSD-3-Clause 25, MIT 3' . PHP_EOL;
    echo 'No lock: licenses unavailable' . PHP_EOL;
    echo 'Gate without a licenses check: ' . $without[0] . PHP_EOL;
    echo 'LICENSE TOOL OK' . PHP_EOL;
}
