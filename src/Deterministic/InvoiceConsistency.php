<?php
declare(strict_types=1);
namespace App\Deterministic;
final readonly class InvoiceConsistency
{
    // Integer cents; a one cent rounding difference is accepted.
    private const int TOLERANCE_CENTS = 1;
    /** @return list<string> */
    public function issues(Invoice $invoice): array
    {
        $sum = 0;
        foreach ($invoice->lines as $line) {
            $sum += $line->quantity * $line->unitPriceCents;
        }
        $issues = [];
        $subtotal = $invoice->subtotalCents;
        if (abs($sum - $subtotal) > self::TOLERANCE_CENTS) {
            $issues[] = 'Review subtotal';
        }
        $total = $invoice->subtotalCents + $invoice->taxCents;
        $printed = $invoice->totalCents;
        if (abs($total - $printed) > self::TOLERANCE_CENTS) {
            $issues[] = 'Review total';
        }
        if ($invoice->taxExempt && $invoice->taxCents !== 0) {
            $issues[] = 'Review exempt tax';
        }
        return $issues;
    }
}
