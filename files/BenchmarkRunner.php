<?php
declare(strict_types=1);
namespace App\Media;
use App\Agents\MediaAgent;
use NeuronAI\Providers\AIProviderInterface;
final readonly class BenchmarkRunner
{
    /** @param array<string, string|int> $expected */
    public function __construct(
        private AIProviderInterface $provider,
        private FixtureSet $fixtures,
        private string $question,
        private array $expected,
    ) {
    }
    public function run(ImageCase $case): BenchmarkRow
    {
        $measurement = $this->fixtures->measure($case);
        $message = $this->fixtures->message($this->question,
            $measurement);
        $thread = 'cost-' . bin2hex(random_bytes(8));
        $agent = MediaAgent::make($this->provider)
            ->setThreadId($thread);
        $start = hrtime(true);
        $probe = $agent->structured($message, CostProbe::class);
        $seconds = (hrtime(true) - $start) / 1e9;
        $usage = $agent->getChatHistory()->getLastMessage()->getUsage();
        return new BenchmarkRow(
            variant: $case->name,
            thread: $thread,
            measurement: $measurement,
            inputTokens: $usage?->inputTokens,
            seconds: $seconds,
            probe: $probe,
            checks: $this->checks($probe),
        );
    }
    /** @return array<string, bool> */
    private function checks(CostProbe $probe): array
    {
        $checks = [];
        foreach ($this->expected as $key => $value) {
            $actual = $probe->{$key};
            $checks[$key] = $actual !== null
                && self::normalize($actual) === self::normalize($value);
        }
        return $checks;
    }
    private static function normalize(string|int $value): string
    {
        return strtolower(preg_replace('/[^a-zA-Z0-9]/', '',
            (string) $value));
    }
}
