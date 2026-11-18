<?php
declare(strict_types=1);
namespace App\Evidence;
use Closure;
use LogicException;
final class EvidenceLog
{
    public const int MAX_BYTES = 8000;
    private const array ORDER = ['structure', 'manifest', 'history',
        'advisories'];
    /** @var array<string, array<string, mixed>> */
    private array $items = [];
    public function __construct(
        private readonly ?Closure $progress = null,
    ) {
    }
    public function begin(string $check): void
    {
        $this->progress?->__invoke("{$check}: running");
    }
    public function record(
        string $check,
        string $status,
        array $facts,
        ?string $id = null,
    ): string {
        return $this->add([
            'id' => $id ?? $check,
            'check' => $check,
            'status' => $status,
            'facts' => $facts,
        ]);
    }
    public function unavailable(string $check, string $reason): string
    {
        return $this->add([
            'id' => $check,
            'check' => $check,
            'status' => 'unavailable',
            'reason' => $reason,
        ]);
    }
    public function get(string $id): ?array
    {
        return $this->items[$id] ?? null;
    }
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $items = $this->items;
        uasort($items, fn (array $a, array $b): int =>
            array_search($a['check'], self::ORDER, true)
            <=> array_search($b['check'], self::ORDER, true));
        return $items;
    }
    /** @return list<string> */
    public function checks(): array
    {
        return array_values(array_unique(
            array_column($this->items, 'check')
        ));
    }
    private function add(array $envelope): string
    {
        $json = json_encode($envelope, JSON_THROW_ON_ERROR
            | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (strlen($json) > self::MAX_BYTES) {
            throw new LogicException(
                "evidence {$envelope['id']} exceeds the byte budget"
            );
        }
        $this->items[$envelope['id']] = $envelope;
        $this->progress?->__invoke(
            "{$envelope['check']}: {$envelope['status']}"
        );
        return $json;
    }
}
