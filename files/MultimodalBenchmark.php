<?php
declare(strict_types=1);
namespace App\Media;
use App\Agents\MediaAgent;
use JsonException;
use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\Message;
use NeuronAI\Chat\Messages\UserMessage;
use RuntimeException;
class MultimodalBenchmark
{
    private array $manifest;
    private array $expected = [];
    private array $metrics = [];
    private array $rows = [];
    private string $question;
    public function __construct(
        private string $directory,
        bool $exercise = false,
    ) {
        $raw = file_get_contents($directory . '/manifest.json');
        if ($raw === false) {
            throw new RuntimeException('Manifest read failed.');
        }
        $this->manifest = json_decode(
            $raw, true, flags: JSON_THROW_ON_ERROR,
        );
        $key = $exercise ? 'exercise_question' : 'question';
        $this->question = $this->manifest[$key];
    }
    public function expect(array $expected): void
    {
        $this->expected = $expected;
    }
    public function cases(): array
    {
        return $this->manifest['variants'];
    }
    public function message(array $case): UserMessage
    {
        $path = $this->directory . '/' . $case['file'];
        $bytes = file_get_contents($path);
        if ($bytes === false || strlen($bytes) > 4_000_000) {
            throw new RuntimeException('Image read or size failed.');
        }
        $size = getimagesizefromstring($bytes);
        if ($size === false || $size['mime'] !== 'image/png'
            || $size[0] !== $case['width']
            || $size[1] !== $case['height']
            || hash('sha256', $bytes) !== $case['sha256']
        ) {
            throw new RuntimeException('Fixture identity differs.');
        }
        $encoded = base64_encode($bytes);
        $this->metrics[$case['name']] = [
            'dimensions' => $size[0] . 'x' . $size[1],
            'raw_bytes' => strlen($bytes),
            'base64_bytes' => strlen($encoded),
            'sha256' => hash('sha256', $bytes),
        ];
        $message = new UserMessage($this->question);
        $message->addContent(new ImageContent(
            content: $encoded,
            sourceType: SourceType::BASE64,
            mediaType: MediaType::PNG,
        ));
        return $message;
    }
    public function agentFor(array $case): MediaAgent
    {
        $thread = 'cost-' . bin2hex(random_bytes(8));
        $this->metrics[$case['name']]['thread'] = $thread;
        return MediaAgent::make()->setThreadId($thread);
    }
    public static function inputLabel(?int $tokens): string
    {
        return $tokens === null ? 'unavailable' : (string) $tokens;
    }
    public function header(): void
    {
        $model = $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5';
        echo 'Provider: Anthropic | Model: ' . $model . PHP_EOL;
        echo 'Question: ' . $this->question . PHP_EOL;
        printf(
            "%-7s %9s %7s %8s %11s %6s %s\n",
            'variant', 'dimensions', 'bytes', 'base64',
            'input', 'secs', 'checks',
        );
    }
    public function row(
        array $case,
        ?Message $reply,
        ?int $tokens,
        float $seconds,
    ): void {
        $text = $reply?->getContent() ?? '';
        try {
            $data = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $data = null;
        }
        $checks = [];
        foreach ($this->expected as $key => $value) {
            $actual = is_array($data) ? ($data[$key] ?? null) : null;
            $checks[$key] = $actual !== null
                && $this->normalize($actual) === $this->normalize($value);
        }
        $passed = count(array_filter($checks));
        $row = [
            'variant' => $case['name'],
            ...$this->metrics[$case['name']],
            'input_tokens' => $tokens,
            'usage' => $reply?->getUsage()?->jsonSerialize(),
            'seconds' => $seconds,
            'observed' => $data,
            'raw_reply' => $text,
            'checks' => $checks,
            'passed' => $checks !== [] && !in_array(false, $checks),
        ];
        $this->rows[] = $row;
        printf(
            "%-7s %9s %7d %8d %11s %6.2f %s %d/%d\n",
            $row['variant'], $row['dimensions'],
            $row['raw_bytes'], $row['base64_bytes'],
            self::inputLabel($tokens), $seconds,
            $row['passed'] ? 'PASS' : 'FAIL',
            $passed, count($checks),
        );
    }
    private function normalize(mixed $value): string
    {
        if (!is_string($value) && !is_int($value)) {
            return '';
        }
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '',
            (string) $value));
    }
    public function finish(): array
    {
        if (count($this->rows) !== count($this->cases())) {
            throw new RuntimeException('Missing a measured variant.');
        }
        $passing = array_values(array_filter(
            $this->rows, static fn (array $r): bool => $r['passed'],
        ));
        foreach ($this->rows as $row) {
            echo $row['variant'] . ' observed: '
                . json_encode($row['observed']) . PHP_EOL;
        }
        if ($passing === []) {
            throw new RuntimeException('No variant passed this task.');
        }
        usort($passing, static fn (array $a, array $b): int =>
            $a['raw_bytes'] <=> $b['raw_bytes']);
        echo 'Smallest passing file this run: '
            . $passing[0]['variant'] . PHP_EOL;
        $storage = dirname($this->directory, 2)
            . '/storage/multimodal-cost';
        if (!is_dir($storage)) {
            mkdir($storage, recursive: true);
        }
        $report = [
            'provider' => 'Anthropic',
            'model' => $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5',
            'question' => $this->question,
            'expected' => $this->expected,
            'rows' => $this->rows,
        ];
        $saved = file_put_contents(
            $storage . '/latest.json',
            json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR),
        );
        if ($saved === false) {
            throw new RuntimeException('Report write failed.');
        }
        echo 'Measurement complete.' . PHP_EOL;
        return $this->rows;
    }
}
