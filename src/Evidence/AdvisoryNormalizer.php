<?php
declare(strict_types=1);
namespace App\Evidence;
final readonly class AdvisoryNormalizer
{
    public const int MAX_ADVISORIES = 20;
    private const array SEVERITIES = ['critical', 'high', 'medium',
        'low'];
    /** @return array{status: string, facts: array}|null */
    public function normalize(string $output, ?int $exitCode): ?array
    {
        $data = json_decode($output, true);
        if (!is_array($data) || !array_key_exists('advisories', $data)
            || !in_array($exitCode, [0, 1, 2, 3], true)) {
            return null;
        }
        $advisories = [];
        foreach ((array) $data['advisories'] as $package => $items) {
            foreach ((array) $items as $item) {
                $advisories[] = $this->advisory(
                    (string) $package, $item);
            }
        }
        $abandoned = [];
        foreach ((array) ($data['abandoned'] ?? []) as $name => $by) {
            $abandoned[] = ['package' => $name, 'replacement' => $by];
        }
        return [
            'status' => $advisories === [] && $abandoned === []
                ? 'clean' : 'findings',
            'facts' => [
                'input' => 'composer.lock',
                'checked_at' => gmdate('Y-m-d\TH:i\Z'),
                'exit_code' => $exitCode,
                'advisory_count' => count($advisories),
                'advisories' => array_slice(
                    $advisories, 0, self::MAX_ADVISORIES),
                'truncated' =>
                    count($advisories) > self::MAX_ADVISORIES,
                'abandoned' => $abandoned,
            ],
        ];
    }
    private function advisory(string $package, mixed $item): array
    {
        $item = is_array($item) ? $item : [];
        $severity = strtolower((string) ($item['severity'] ?? ''));
        return [
            'package' => $item['packageName'] ?? $package,
            'affected' => $item['affectedVersions'] ?? null,
            'id' => $item['advisoryId'] ?? null,
            'cve' => $item['cve'] ?? null,
            'severity' => in_array($severity, self::SEVERITIES, true)
                ? $severity : 'unknown',
            'title' => mb_substr((string) ($item['title'] ?? ''),
                0, 100),
            'link' => mb_substr((string) ($item['link'] ?? ''),
                0, 200),
        ];
    }
}
