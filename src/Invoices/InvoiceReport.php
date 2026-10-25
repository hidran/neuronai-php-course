<?php
declare(strict_types=1);
namespace App\Invoices;
use RuntimeException;
final class InvoiceReport
{
    /** @var list<array<string, mixed>> */
    private array $results = [];
    /** @param list<string> $review */
    public function record(InvoiceSource $source, Invoice $invoice,
        array $review, ?int $inputTokens, float $seconds): void
    {
        $status = $review === [] ? 'accepted' : 'review_required';
        $this->results[] = [
            'source' => $source->name,
            'sha256' => $source->sha256,
            'invoice' => $invoice,
            'review' => $review,
            'status' => $status,
            'input_tokens' => $inputTokens,
            'seconds' => $seconds,
        ];
        echo 'Source: ' . $source->name . ' | sha256 '
            . substr($source->sha256, 0, 12) . PHP_EOL;
        echo "Invoice: {$invoice->number} | {$invoice->supplierName}"
            . PHP_EOL;
        echo "Date: {$invoice->issueDate} | Currency: "
            . $invoice->currency->value . PHP_EOL;
        echo 'Subtotal ' . self::money($invoice->subtotalCents)
            . ' | Tax ' . self::money($invoice->vatCents)
            . ' | Total ' . self::money($invoice->totalCents) . PHP_EOL;
        foreach ($invoice->lines as $line) {
            echo "{$line->description} | qty {$line->quantity} | unit "
                . self::money($line->unitPriceCents) . ' | line '
                . self::money($line->lineTotalCents) . PHP_EOL;
        }
        foreach ($review as $issue) {
            echo 'Review: ' . $issue . PHP_EOL;
        }
        echo "State: {$status}" . PHP_EOL;
        echo 'Reported input: ' . ($inputTokens ?? 'unavailable')
            . sprintf(' | %.1f s', $seconds) . PHP_EOL;
    }
    public function failure(string $name, string $category,
        string $detail): void {
        echo "Source: {$name}" . PHP_EOL;
        echo "State: {$category} | {$detail}" . PHP_EOL;
    }
    public function compare(): void
    {
        echo 'Image comparison: source | reported input | state'
            . PHP_EOL;
        foreach ($this->results as $result) {
            echo $result['source'] . ' | '
                . ($result['input_tokens'] ?? 'unavailable') . ' | '
                . $result['status'] . PHP_EOL;
        }
    }
    public function save(string $storage): void
    {
        if (!is_dir($storage)) {
            mkdir($storage, recursive: true);
        }
        $json = json_encode($this->results,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
        $saved = file_put_contents($storage . '/latest.json', $json);
        if ($saved === false) {
            throw new RuntimeException('Invoice report write failed.');
        }
    }
    public static function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
