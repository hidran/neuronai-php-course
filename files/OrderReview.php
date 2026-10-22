<?php
declare(strict_types=1);
namespace App;
use App\Output\Order;
class OrderReview
{
    public static function issues(Order $order, array $catalog): array
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
            $price = $catalog[$line->sku] ?? null;
            if (!is_int($price)) {
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
