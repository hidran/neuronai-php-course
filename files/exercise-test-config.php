<?php
declare(strict_types=1);
// Reference solution of the exercise, runnable without a model: php exercise-test-config.php <repo-auditor project>
$project = $argv[1] ?? throw new RuntimeException('project path');
require $project . '/vendor/autoload.php';
use App\Evidence\EvidenceLog;
use NeuronAI\Tools\Tool;
final class TestConfigTool extends Tool
{
    protected string $name = 'read_test_config';
    protected ?string $description = 'The PHPUnit configuration of the '
        . 'project: which file and which test suites.';
    public function __construct(
        private readonly string $root,
        private readonly EvidenceLog $log,
    ) {
    }
    public function __invoke(): string
    {
        $file = array_find(['phpunit.xml', 'phpunit.xml.dist'],
            fn (string $name): bool => is_file("{$this->root}/{$name}"));
        if ($file === null) {
            return $this->log->unavailable('tests', 'no PHPUnit configuration');
        }
        if (filesize("{$this->root}/{$file}") > 16384) {
            return $this->log->unavailable('tests',
                'PHPUnit configuration is over 16 KB');
        }
        $xml = @simplexml_load_string(
            (string) file_get_contents("{$this->root}/{$file}"));
        if ($xml === false) {
            return $this->log->unavailable('tests', 'invalid PHPUnit XML');
        }
        $suites = [];
        foreach ($xml->xpath('//testsuite') ?: [] as $suite) {
            $suites[] = (string) $suite['name'];
        }
        return $this->log->record('tests', 'ok', [
            'file' => $file,
            'suites' => array_slice($suites, 0, 10),
        ]);
    }
}
$results = [];
foreach (['library', 'app', 'legacy'] as $fixture) {
    $envelope = json_decode(
        new TestConfigTool("{$project}/fixtures/{$fixture}", new EvidenceLog())(), true);
    $results[$fixture] = $envelope['facts'] ?? $envelope['reason'];
    echo $fixture, ': ', json_encode($results[$fixture]), PHP_EOL;
}
$ok = $results === [
    'library' => ['file' => 'phpunit.xml.dist', 'suites' => ['unit']],
    'app' => ['file' => 'phpunit.xml', 'suites' => ['feature']],
    'legacy' => 'no PHPUnit configuration',
];
echo $ok ? "EXERCISE OK\n" : "EXERCISE FAILED\n";
exit($ok ? 0 : 1);
