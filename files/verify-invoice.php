<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
foreach (['Currency', 'InvoiceLine', 'Invoice', 'InvoiceSource',
    'InvoiceMessageFactory', 'InvoiceConsistency', 'InvoiceOracle',
    'InvoiceReport', 'InvoiceRunner', 'InvoiceAgent'] as $class) {
    require __DIR__ . "/{$class}.php";
}
use App\Invoices\Currency;
use App\Invoices\Invoice;
use App\Invoices\InvoiceConsistency;
use App\Invoices\InvoiceMessageFactory;
use App\Invoices\InvoiceOracle;
use App\Invoices\InvoiceReport;
use App\Invoices\InvoiceRunner;
use App\Invoices\InvoiceSource;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ContentBlocks\FileContent;
use NeuronAI\Chat\Messages\ContentBlocks\ImageContent;
use NeuronAI\StructuredOutput\JsonSchema;
use NeuronAI\Testing\FakeAIProvider;
$schema = JsonSchema::make()->generate(Invoice::class);
if (($schema['properties']['currency']['enum'] ?? null) !== ['EUR', 'USD', 'GBP']
    || count($schema['required']) !== 8) {
    throw new RuntimeException('schema: ' . json_encode($schema));
}
$manifest = json_decode(file_get_contents(__DIR__ . '/manifest.json'), true);
$oracle = InvoiceOracle::fromManifest(__DIR__ . '/manifest.json');
$normal = InvoiceSource::fromDirectory(__DIR__, 'normal.pdf');
$png = InvoiceSource::fromDirectory(__DIR__, 'normal.png');
$factory = new InvoiceMessageFactory();
$blocks = $factory->message($normal)->getContentBlocks();
if (!$blocks[1] instanceof FileContent || $blocks[1]->filename !== 'normal.pdf') {
    throw new RuntimeException('pdf block wrong');
}
if (!$factory->message($png)->getContentBlocks()[1] instanceof ImageContent) {
    throw new RuntimeException('png block wrong');
}
foreach (['../x.pdf', 'missing.pdf', 'manifest.json'] as $bad) {
    try {
        InvoiceSource::fromDirectory(__DIR__, $bad);
        throw new LogicException("accepted {$bad}");
    } catch (InvalidArgumentException) {
    }
}
$expected = $oracle->expected($normal);
if (!$expected instanceof Invoice || $expected->currency !== Currency::EUR
    || $expected->totalCents !== 4560) {
    throw new RuntimeException('oracle did not hydrate the manifest');
}
$consistency = new InvoiceConsistency();
if ($consistency->issues($expected) !== []) {
    throw new RuntimeException('normal fixture flagged');
}
$inconsistent = $oracle->expected(
    InvoiceSource::fromDirectory(__DIR__, 'inconsistent.pdf'));
if ($consistency->issues($inconsistent) === []) {
    throw new RuntimeException('inconsistent fixture not flagged');
}
$normalJson = json_encode($manifest['cases']['normal']['invoice']);
$wrongJson = json_encode(['number' => '0007-B'] + $manifest['cases']['normal']['invoice']);
$fake = new FakeAIProvider(new AssistantMessage($normalJson),
    new AssistantMessage($wrongJson), new AssistantMessage('no json'),
    new AssistantMessage('still no json'));
$report = new InvoiceReport();
$runner = new InvoiceRunner($fake, __DIR__, $factory, $consistency,
    $oracle, $report);
ob_start();
$completed = $runner->run(['normal.pdf', 'normal.png', 'alternate.pdf',
    'nope.pdf']);
$output = ob_get_clean();
if ($completed) {
    throw new RuntimeException('failures not reported');
}
foreach (['State: accepted', 'Subtotal 38.00 | Tax 7.60 | Total 45.60',
    'Source differs at number', 'State: review_required',
    'State: validation_failure', 'State: local_file_problem'] as $label) {
    if (!str_contains($output, $label)) {
        throw new RuntimeException("missing '{$label}': {$output}");
    }
}
$storage = sys_get_temp_dir() . '/inv-' . bin2hex(random_bytes(4));
$report->save($storage);
$saved = json_decode(file_get_contents($storage . '/latest.json'), true);
if (count($saved) !== 2 || $saved[0]['invoice']['totalCents'] !== 4560) {
    throw new RuntimeException('report file wrong');
}
array_map(unlink(...), glob($storage . '/*'));
rmdir($storage);
echo 'INVOICE OK' . PHP_EOL;
