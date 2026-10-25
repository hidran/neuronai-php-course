<?php
declare(strict_types=1);
namespace App\Orders;
use App\Output\Order;
final readonly class OrderReview
{
    private const string SKU_PATTERN = '/^[A-Z]{2}-[0-9]{4}$/';
    public function __construct(private Catalog $catalog)
    {
    }
    /** @return list<string> */
    public function issues(Order $order): array
    {
        $issues = [];
        $expected = 0;
        $known = true;
        if ($order->customerName === null) {
            $issues[] = 'Customer name missing.';
        }
        if ($order->deliveryAddress === null) {
            $issues[] = 'Delivery address missing.';
        }
        foreach ($order->lines as $line) {
            if (preg_match(self::SKU_PATTERN, $line->sku) !== 1) {
                $issues[] = "Malformed SKU: {$line->sku}.";
                $known = false;
                continue;
            }
            $price = $this->catalog->priceOf($line->sku);
            if ($price === null) {
                $issues[] = "Unknown SKU: {$line->sku}.";
                $known = false;
                continue;
            }
            $expected += $line->quantity * $price;
            if ($line->unitPriceCents === null) {
                $issues[] = "Unit price missing: {$line->sku}.";
            } elseif ($line->unitPriceCents !== $price) {
                $issues[] = "Catalog price differs: {$line->sku}.";
            }
        }
        if ($order->statedTotalCents === null) {
            $issues[] = 'Stated total missing.';
        } elseif ($known && $order->statedTotalCents !== $expected) {
            $issues[] = "Catalog total differs: expected {$expected}.";
        }
        return $issues;
    }
}
