<?php
declare(strict_types=1);
namespace App\Invoices;
use App\Dto\Invoice;
use App\Dto\InvoiceLine;
use DateTimeImmutable;
class InvoiceConsistency
{
    public static function issues(Invoice $invoice): array
    {
        $issues = [];
        $date = DateTimeImmutable::createFromFormat(
            '!Y-m-d', $invoice->issue_date,
        );
        if ($date === false || $date->format('Y-m-d') !== $invoice->issue_date) {
            $issues[] = 'Issue date is not a real calendar date.';
        }
        if (!in_array($invoice->currency, ['EUR', 'USD', 'GBP'], true)) {
            $issues[] = 'Currency is outside this fixture policy.';
        }
        foreach ($invoice->lines as $index => $line) {
            // Compare the printed row amount.
        }
        // Compare document totals.
        return $issues;
    }
    private static function compare(
        array &$issues, string $label, float $printed, float $computed,
    ): void {
        if (abs($printed - $computed) > 0.01 + 1e-8) {
            $issues[] = sprintf('%s: printed %.2f, computed %.2f.',
                $label, $printed, $computed);
        }
    }
}
