<?php
declare(strict_types=1);
namespace App\Knowledge;
/**
 * The decision table: one mechanism per authority, with what you accept
 * when you choose it. Pure data and lookups, no printing.
 */
final readonly class AuthorityRouter
{
    /**
     * @param array<string, array{authority: Authority,
     *     accepting: list<string>}> $table
     */
    public function __construct(private array $table)
    {
    }
    public function mechanismFor(Authority $authority): ?string
    {
        foreach ($this->table as $mechanism => $row) {
            if ($row['authority'] === $authority) {
                return $mechanism;
            }
        }
        return null;
    }
    /** @return list<string> */
    public function accepting(string $mechanism): array
    {
        return $this->table[$mechanism]['accepting'] ?? [];
    }
    /** @return array<string, Authority> mechanism => authority */
    public function rows(): array
    {
        return array_map(static fn (array $row): Authority
            => $row['authority'], $this->table);
    }
}
