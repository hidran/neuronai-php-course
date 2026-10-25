<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Invoices\Currency;
use App\Invoices\Invoice;
use App\Invoices\InvoiceConsistency;
use App\Invoices\InvoiceLine;
use App\Invoices\InvoiceMessageFactory;
use App\Invoices\InvoiceOracle;
use App\Invoices\InvoiceReport;
use App\Invoices\InvoiceRunner;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
use NeuronAI\Testing\FakeAIProvider;
function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
$root = dirname(__DIR__);
$fixtures = $root . '/fixtures/invoices';
$oracle = InvoiceOracle::fromManifest($fixtures . '/manifest.json');
$consistency = new InvoiceConsistency();
$invoice = new Invoice('EX-1', 'Local Fixture Works', '2026-02-30',
    Currency::EUR, 1300, 0, 1300,
    [new InvoiceLine('Checker row', 2, 500, 1000)]);
$issues = $consistency->issues($invoice);
check(count($issues) === 2, 'Expected a date and a subtotal issue.');
echo 'Local review: calendar date and subtotal verified.' . PHP_EOL;
try {
    Deserializer::make()->fromJson(
        '{"number":"X","supplierName":"Y","issueDate":"2026-01-01",'
        . '"currency":"ZZZ","subtotalCents":1,"vatCents":0,'
        . '"totalCents":1,"lines":[]}', Invoice::class);
    throw new RuntimeException('Unknown currency was accepted.');
} catch (DeserializerException) {
    echo 'Currency enum: ZZZ rejected before validation.' . PHP_EOL;
}
$fake = new FakeAIProvider(new AssistantMessage('not json at all'),
    new AssistantMessage('still not json'));
$report = new InvoiceReport();
$runner = new InvoiceRunner($fake, $fixtures,
    new InvoiceMessageFactory(), $consistency, $oracle, $report);
ob_start();
$completed = $runner->run(['missing.pdf', 'normal.pdf']);
$output = ob_get_clean();
check(!$completed, 'Failures must be reported.');
check(str_contains($output, 'State: local_file_problem'),
    'A missing file must be a local problem.');
check(str_contains($output, 'State: validation_failure'),
    'A reply without JSON must be a validation failure.');
echo 'Failure categories: local file and validation kept apart.'
    . PHP_EOL;
if (!in_array('--live', $argv, true)) {
    echo 'Live extraction skipped. Add --live to run it.' . PHP_EOL;
    echo 'Exercise complete.' . PHP_EOL;
    exit(0);
}
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$report = new InvoiceReport();
$runner = new InvoiceRunner($provider, $fixtures,
    new InvoiceMessageFactory(), $consistency, $oracle, $report);
check($runner->run(['exercise.pdf']), 'Live extraction failed.');
$report->save($root . '/storage/invoices');
$saved = json_decode(
    file_get_contents($root . '/storage/invoices/latest.json'),
    associative: true, flags: JSON_THROW_ON_ERROR);
check(count($saved) === 1 && $saved[0]['status'] === 'accepted'
    && $saved[0]['invoice']['number'] === 'EX/0073'
    && $saved[0]['invoice']['totalCents'] === 4440,
    'Distinct source extraction failed.');
echo 'Distinct source: every mandatory field verified.' . PHP_EOL;
echo 'Exercise complete.' . PHP_EOL;
