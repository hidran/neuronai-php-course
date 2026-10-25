<?php
declare(strict_types=1);
namespace App\Invoices;
use DateTimeImmutable;
final readonly class InvoiceConsistency
{
    /** @return list<string> */
    public function issues(Invoice $invoice): array
    {
        $issues = [];
        $date = DateTimeImmutable::createFromFormat('!Y-m-d',
            $invoice->issueDate);
        if ($date === false
            || $date->format('Y-m-d') !== $invoice->issueDate) {
            $issues[] = 'Issue date is not a real calendar date.';
        }
        $sum = 0;
        foreach ($invoice->lines as $index => $line) {
            $computed = $line->quantity * $line->unitPriceCents;
            $sum += $line->lineTotalCents;
            if ($line->lineTotalCents !== $computed) {
                $issues[] = sprintf('Row %d: printed %d, computed %d.',
                    $index + 1, $line->lineTotalCents, $computed);
            }
        }
        if ($invoice->subtotalCents !== $sum) {
            $issues[] = sprintf('Subtotal: printed %d, computed %d.',
                $invoice->subtotalCents, $sum);
        }
        $total = $invoice->subtotalCents + $invoice->vatCents;
        if ($invoice->totalCents !== $total) {
            $issues[] = sprintf('Grand total: printed %d, computed %d.',
                $invoice->totalCents, $total);
        }
        return $issues;
    }
}
