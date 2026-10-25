<?php
declare(strict_types=1);
namespace App\Invoices;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use RuntimeException;
final readonly class InvoiceOracle
{
    /**
     * @param array<string, string> $cases sha256 => case name
     * @param array<string, string> $expected case name => invoice JSON
     */
    public function __construct(
        private array $cases,
        private array $expected,
    ) {
    }
    public static function fromManifest(string $path): self
    {
        $json = file_get_contents($path)
            ?: throw new RuntimeException('Manifest read failed.');
        $manifest = json_decode($json, associative: true,
            flags: JSON_THROW_ON_ERROR);
        $cases = [];
        foreach ($manifest['files'] as $file) {
            $cases[$file['sha256']] = $file['case'];
        }
        $expected = array_map(
            static fn (array $case): string
                => json_encode($case['invoice'], JSON_THROW_ON_ERROR),
            $manifest['cases'],
        );
        return new self($cases, $expected);
    }
    public function expected(InvoiceSource $source): ?Invoice
    {
        $case = $this->cases[$source->sha256] ?? null;
        if ($case === null) {
            return null;
        }
        return Deserializer::make()->fromJson($this->expected[$case],
            Invoice::class);
    }
    /** @return list<string> */
    public function differences(InvoiceSource $source, Invoice $actual)
        : array
    {
        $expected = $this->expected($source);
        if ($expected === null) {
            return ['No fixture oracle: human source review required.'];
        }
        $issues = [];
        $want = json_decode(json_encode($expected), true);
        $got = json_decode(json_encode($actual), true);
        foreach ($want as $field => $value) {
            if (($got[$field] ?? null) !== $value) {
                $issues[] = "Source differs at {$field}: expected "
                    . json_encode($value) . ', observed '
                    . json_encode($got[$field] ?? null) . '.';
            }
        }
        return $issues;
    }
}
