<?php
declare(strict_types=1);
namespace App\Invoices;
use App\Agents\InvoiceAgent;
use App\Dto\Invoice;
use App\Dto\InvoiceLine;
use InvalidArgumentException;
use NeuronAI\Chat\Enums\MediaType;
use NeuronAI\Chat\Enums\SourceType;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
use NeuronAI\StructuredOutput\Validation\Validator;
use RuntimeException;
use Throwable;
class InvoiceLab
{
    private array $manifest;
    private array $results = [];
    public function __construct(private string $root)
    {
        $this->manifest = json_decode(file_get_contents(
            $root . '/fixtures/invoices/manifest.json',
        ), true, flags: JSON_THROW_ON_ERROR);
    }
    public function read(string $path): array
    {
        if (!str_starts_with($path, '/')) {
            $path = $this->root . '/' . $path;
        }
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException('Local file unreadable.');
        }
        $size = filesize($path);
        if ($size === false || $size < 1 || $size > 3_000_000) {
            throw new InvalidArgumentException('Local size rejected.');
        }
        $bytes = file_get_contents($path);
        if ($bytes === false) {
            throw new InvalidArgumentException('Local read failed.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (!in_array($mime, ['application/pdf', 'image/png'], true)) {
            throw new InvalidArgumentException('Unsupported local format.');
        }
        return [
            'path' => $path, 'bytes' => $bytes, 'mime' => $mime,
            'sha256' => hash('sha256', $bytes),
            'started_ns' => hrtime(true),
        ];
    }
    public function message(array $source): UserMessage
    {
        $message = new UserMessage(
            'Extract this invoice exactly as printed. Read every row.',
        );
        $encoded = base64_encode($source['bytes']);
        $block = $source['mime'] === 'application/pdf'
            ? new FileContent(
                content: $encoded,
                sourceType: SourceType::BASE64,
                mediaType: MediaType::PDF,
                filename: basename($source['path']),
            )
            : new ImageContent(
                content: $encoded,
                sourceType: SourceType::BASE64,
                mediaType: MediaType::PNG,
            );
        $message->addContent($block);
        return $message;
    }
    public function thread(): string
    {
        return 'invoice-' . bin2hex(random_bytes(8));
    }
    public function report(
        Invoice $invoice, array $source, InvoiceAgent $agent,
    ): void {
        $data = json_decode(json_encode($invoice), true);
        $issues = InvoiceConsistency::issues($invoice);
        $case = null;
        foreach ($this->manifest['files'] as $known) {
            if ($known['sha256'] === $source['sha256']) {
                $case = $known['case'];
                break;
            }
        }
        $fidelity = [];
        if ($case === null) {
            $fidelity[] = 'No fixture oracle: human source review required.';
        } else {
            $expected = $this->manifest['cases'][$case]['invoice'];
            $this->compareFields($expected, $data, '', $fidelity);
        }
        $review = [...$issues, ...$fidelity];
        $input = 0;
        $calls = 0;
        $missing = false;
        foreach ($agent->getChatHistory()->getMessages() as $message) {
            if ($message->getRole() !== 'assistant') {
                continue;
            }
            $calls++;
            $usage = $message->getUsage();
            if ($usage === null) {
                $missing = true;
            } else {
                $input += $usage->inputTokens;
            }
        }
        $input = $missing || $calls === 0 ? null : $input;
        $result = [
            'source' => [
                'path' => $source['path'], 'sha256' => $source['sha256'],
                'mime' => $source['mime'],
            ],
            'invoice' => $data,
            'status' => $review === [] ? 'accepted' : 'review_required',
            'review' => $review, 'source_checks' => $fidelity,
            'input_tokens' => $input, 'inference_calls' => $calls,
            'seconds' => (hrtime(true) - $source['started_ns']) / 1e9,
        ];
        $this->results[] = $result;
        echo 'Source: ' . basename($source['path']) . ' | sha256 '
            . substr($source['sha256'], 0, 12) . PHP_EOL;
        echo 'Invoice: ' . $invoice->number . ' | '
            . $invoice->supplier_name . PHP_EOL;
        echo 'Date: ' . $invoice->issue_date . ' | Currency: '
            . $invoice->currency . PHP_EOL;
        printf("Subtotal %.2f | Tax %.2f | Total %.2f\n",
            $invoice->subtotal, $invoice->vat_amount, $invoice->total);
        foreach ($invoice->lines as $line) {
            printf("%s | qty %g | unit %.2f | line %.2f\n",
                $line->description, $line->quantity,
                $line->unit_price, $line->line_total);
        }
        echo 'Source fidelity: '
            . ($fidelity === [] ? 'all fields match' : 'review') . PHP_EOL;
        foreach ($review as $issue) {
            echo 'Review: ' . $issue . PHP_EOL;
        }
        echo 'State: ' . $result['status'] . PHP_EOL;
        echo 'Reported input: ' . ($input ?? 'unavailable')
            . ' | inference calls: ' . $calls . PHP_EOL;
    }
    private function compareFields(
        mixed $expected, mixed $actual, string $path, array &$issues,
    ): void {
        if (is_array($expected)) {
            if (!is_array($actual) || count($expected) !== count($actual)) {
                $issues[] = 'Source differs at ' . $path . ' (shape/length).';
                return;
            }
            foreach ($expected as $key => $value) {
                $next = $path === '' ? (string) $key : $path . '.' . $key;
                $this->compareFields($value, $actual[$key] ?? null,
                    $next, $issues);
            }
            return;
        }
        $same = is_int($expected) || is_float($expected)
            ? is_numeric($actual) && abs($expected - $actual) < 1e-8
            : $actual === $expected;
        if (!$same) {
            $issues[] = 'Source differs at ' . $path . ': expected '
                . json_encode($expected) . ', observed '
                . json_encode($actual) . '.';
        }
    }
    public static function directCheck(): void
    {
        $invoice = new Invoice();
        $invoice->number = 'DIRECT-01';
        $invoice->supplier_name = 'Local Fixture Works';
        $invoice->issue_date = '2026-10-18';
        $invoice->currency = 'EUR';
        $invoice->subtotal = 13;
        $invoice->vat_amount = 0;
        $invoice->total = 13;
        $line = new InvoiceLine();
        $line->description = 'Local checker row';
        $line->quantity = 2;
        $line->unit_price = 5;
        $line->line_total = 10;
        $invoice->lines = [$line];
        if (InvoiceConsistency::issues($invoice) === []) {
            throw new RuntimeException('Direct warning path failed.');
        }
        echo 'Direct checker review: subtotal mismatch verified.' . PHP_EOL;
        $line->quantity = 0;
        if (Validator::validate($invoice) === []) {
            throw new RuntimeException('ArrayOf did not validate the line.');
        }
        echo 'Nested ArrayOf validation: rejected zero quantity.' . PHP_EOL;
    }
    public function run(array $argv, callable $extract): void
    {
        $mode = $argv[1] ?? '';
        $names = match ($mode) {
            '--other-layouts' => ['alternate.pdf', 'tax-exempt.pdf'],
            '--review' => ['inconsistent.pdf'],
            '--compare' => ['invoice-full.png', 'invoice-small.png'],
            '--exercise' => ['exercise.pdf'],
            default => [$mode === '' ? 'normal.pdf' : $mode],
        };
        $failed = false;
        foreach ($names as $name) {
            $path = str_contains($name, '/') ? $name
                : 'fixtures/invoices/' . $name;
            try {
                $extract($path);
            } catch (Throwable $error) {
                $failed = true;
                $category = 'request_failure';
                for ($cause = $error; $cause !== null;
                    $cause = $cause->getPrevious()
                ) {
                    if ($cause instanceof AgentException
                        || $cause instanceof DeserializerException
                    ) {
                        $category = 'validation_failure';
                    }
                }
                echo 'State: ' . $category . ' | '
                    . ($error instanceof InvalidArgumentException
                        ? 'local file problem' : $error::class) . PHP_EOL;
            }
        }
        if ($mode === '--review' || $mode === '--exercise') {
            self::directCheck();
        }
        if ($mode === '--compare') {
            echo 'Image comparison: reported input, source checks' . PHP_EOL;
            foreach ($this->results as $result) {
                echo basename($result['source']['path']) . ' | '
                    . ($result['input_tokens'] ?? 'unavailable') . ' | '
                    . $result['status'] . PHP_EOL;
            }
        }
        $storage = $this->root . '/storage/invoices';
        if (!is_dir($storage)) {
            mkdir($storage, recursive: true);
        }
        $saved = file_put_contents($storage . '/latest.json', json_encode(
            $this->results, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR,
        ));
        if ($saved === false) {
            throw new RuntimeException('Invoice report write failed.');
        }
        if ($failed) {
            throw new RuntimeException('Invoice run did not complete.');
        }
        echo 'Invoice extraction complete.' . PHP_EOL;
    }
}
