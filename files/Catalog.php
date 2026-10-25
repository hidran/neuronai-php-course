<?php
declare(strict_types=1);
namespace App\Orders;
use InvalidArgumentException;
use RuntimeException;
final readonly class Catalog
{
    /** @param array<string, int> $pricesCents */
    public function __construct(private array $pricesCents)
    {
        foreach ($pricesCents as $sku => $price) {
            if (!is_string($sku) || !is_int($price) || $price <= 0) {
                throw new InvalidArgumentException(
                    "Catalog entry {$sku} needs positive integer cents."
                );
            }
        }
    }
    public static function fromJsonFile(string $path): self
    {
        $json = file_get_contents($path)
            ?: throw new RuntimeException("Cannot read {$path}.");
        return new self(json_decode($json, associative: true,
            flags: JSON_THROW_ON_ERROR));
    }
    public function priceOf(string $sku): ?int
    {
        return $this->pricesCents[$sku] ?? null;
    }
}
