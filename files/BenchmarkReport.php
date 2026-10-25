<?php
declare(strict_types=1);
namespace App\Media;
use App\Config\Settings;
use RuntimeException;
final readonly class BenchmarkReport
{
    public function __construct(private string $storage)
    {
    }
    public function header(Settings $settings, string $question): void
    {
        echo 'Provider: ' . $settings->provider->value
            . ' | Model: ' . $settings->model . PHP_EOL;
        echo 'Question: ' . $question . PHP_EOL;
        printf("%-7s %9s %7s %8s %11s %6s %s\n", 'variant',
            'dimensions', 'bytes', 'base64', 'input', 'secs', 'checks');
    }
    public function row(BenchmarkRow $row): void
    {
        $m = $row->measurement;
        printf("%-7s %9s %7d %8d %11s %6.2f %s %d/%d\n",
            $row->variant, $m->dimensions, $m->rawBytes,
            $m->base64Bytes,
            $row->inputTokens ?? 'unavailable', $row->seconds,
            $row->passed() ? 'PASS' : 'FAIL',
            count(array_filter($row->checks)), count($row->checks));
    }
    /** @param list<BenchmarkRow> $rows */
    public function finish(array $rows, Settings $settings): void
    {
        foreach ($rows as $row) {
            echo $row->variant . ' observed: '
                . json_encode($row->probe) . PHP_EOL;
        }
        $passing = array_filter($rows,
            static fn (BenchmarkRow $row): bool => $row->passed());
        if ($passing === []) {
            throw new RuntimeException('No variant passed this task.');
        }
        usort($passing, static fn (BenchmarkRow $a, BenchmarkRow $b)
            => $a->measurement->rawBytes <=> $b->measurement->rawBytes);
        echo 'Smallest passing file this run: '
            . array_first($passing)->variant . PHP_EOL;
        $this->save($rows, $settings);
        echo 'Measurement complete.' . PHP_EOL;
    }
    /** @param list<BenchmarkRow> $rows */
    private function save(array $rows, Settings $settings): void
    {
        if (!is_dir($this->storage)) {
            mkdir($this->storage, recursive: true);
        }
        $report = [
            'provider' => $settings->provider->value,
            'model' => $settings->model,
            'rows' => array_map(static fn (BenchmarkRow $row) => [
                'variant' => $row->variant,
                'thread' => $row->thread,
                'dimensions' => $row->measurement->dimensions,
                'raw_bytes' => $row->measurement->rawBytes,
                'base64_bytes' => $row->measurement->base64Bytes,
                'input_tokens' => $row->inputTokens,
                'seconds' => $row->seconds,
                'observed' => $row->probe,
                'checks' => $row->checks,
                'passed' => $row->passed(),
            ], $rows),
        ];
        $json = json_encode($report,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $saved = file_put_contents($this->storage . '/latest.json',
            $json);
        if ($saved === false) {
            throw new RuntimeException('Report write failed.');
        }
    }
}
