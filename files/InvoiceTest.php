<?php
declare(strict_types=1);
use App\Deterministic\Invoice;
use App\Deterministic\InvoiceConsistency;
use App\Deterministic\InvoiceLine;
use NeuronAI\StructuredOutput\Validation\Validator;
use PHPUnit\Framework\TestCase;
final class InvoiceTest extends TestCase
{
    private function invoice(
        string $description = 'Workshop',
        int $quantity = 2,
        int $subtotalCents = 5000,
        int $taxCents = 1000,
        int $totalCents = 6000,
        bool $taxExempt = false,
    ): Invoice {
        return new Invoice(
            lines: [new InvoiceLine($description, $quantity, 2500)],
            subtotalCents: $subtotalCents,
            taxCents: $taxCents,
            totalCents: $totalCents,
            taxExempt: $taxExempt,
        );
    }
    public function testValidNestedInvoice(): void
    {
        self::assertSame([], Validator::validate($this->invoice()));
    }
    public function testEmptyDescriptionInsideArrayIsRejected(): void
    {
        self::assertNotEmpty(Validator::validate(
            $this->invoice(description: '')
        ));
    }
    public function testNonpositiveQuantityInsideArrayIsRejected(): void
    {
        self::assertNotEmpty(Validator::validate(
            $this->invoice(quantity: 0)
        ));
    }
    public function testArithmeticMatches(): void
    {
        self::assertSame([],
            new InvoiceConsistency()->issues($this->invoice()));
    }
    public function testSubtotalMismatchNeedsReview(): void
    {
        self::assertContains('Review subtotal',
            new InvoiceConsistency()->issues(
                $this->invoice(subtotalCents: 4900)
            ));
    }
    public function testToleranceInclusiveAtOneCent(): void
    {
        $review = new InvoiceConsistency();
        self::assertSame([], $review->issues($this->invoice(
            totalCents: 6001)));
        self::assertSame([], $review->issues($this->invoice(
            totalCents: 5999)));
        self::assertContains('Review total', $review->issues(
            $this->invoice(totalCents: 6002)));
        self::assertContains('Review total', $review->issues(
            $this->invoice(totalCents: 5998)));
    }
    public function testTaxExemptRequiresZeroTax(): void
    {
        $review = new InvoiceConsistency();
        self::assertSame([], $review->issues($this->invoice(
            taxCents: 0, totalCents: 5000, taxExempt: true)));
        self::assertContains('Review exempt tax', $review->issues(
            $this->invoice(taxExempt: true)));
    }
}
