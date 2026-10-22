<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\InvoiceAgent;
use App\Dto\Invoice;
use App\Dto\InvoiceLine;
use App\Invoices\InvoiceConsistency;
use App\Invoices\InvoiceLab;
use Dotenv\Dotenv;
use NeuronAI\Exceptions\AgentException;
Dotenv::createImmutable(dirname(__DIR__))->load();
$root = dirname(__DIR__);
$lab = new InvoiceLab($root);
$extract = static function (string $path) use ($lab): void {
    $source = $lab->read($path);
    $message = $lab->message($source);
    $agent = InvoiceAgent::make()->setThreadId($lab->thread());
    $invoice = $agent->structured($message, maxRetries: 1);
    $lab->report($invoice, $source, $agent);
};
$lab->run(['exercise', '--exercise'], $extract);
$reportPath = $root . '/storage/invoices/latest.json';
$reportText = file_get_contents($reportPath);
$report = json_decode($reportText, true, flags: JSON_THROW_ON_ERROR);
if (count($report) !== 1 || $report[0]['status'] !== 'accepted'
    || $report[0]['source_checks'] !== []
    || $report[0]['invoice']['number'] !== 'EX/0073'
    || abs($report[0]['invoice']['total'] - 44.4) > 1e-8
) {
    throw new RuntimeException('Distinct source extraction failed.');
}
$invoice = new Invoice();
foreach ($report[0]['invoice'] as $key => $value) {
    if ($key !== 'lines') {
        $invoice->$key = $value;
    }
}
$invoice->lines = [];
foreach ($report[0]['invoice']['lines'] as $data) {
    $line = new InvoiceLine();
    foreach ($data as $key => $value) {
        $line->$key = $value;
    }
    $invoice->lines[] = $line;
}
$invoice->issue_date = '2026-02-30';
$invoice->currency = 'ZZZ';
$issues = InvoiceConsistency::issues($invoice);
if (count($issues) < 2) {
    throw new RuntimeException('Calendar/currency review was lost.');
}
foreach ([true, false] as $validation) {
    $probe = new InvoiceLab($root);
    ob_start();
    try {
        $probe->run(['probe', 'missing.pdf'],
            static function (string $path) use ($validation): void {
                if ($validation) {
                    throw new AgentException('Invalid DTO probe.');
                }
                throw new InvalidArgumentException('Missing local file.');
            }
        );
    } catch (RuntimeException) {
    }
    $output = ob_get_clean();
    $expected = $validation ? 'validation_failure' : 'request_failure';
    if (!str_contains($output, 'State: ' . $expected)) {
        throw new RuntimeException('Failure categories were merged.');
    }
}
file_put_contents($reportPath, $reportText);
echo 'Distinct source: every mandatory field verified.' . PHP_EOL;
echo 'Calendar, currency and failure states verified.' . PHP_EOL;
echo 'Exercise complete.' . PHP_EOL;
