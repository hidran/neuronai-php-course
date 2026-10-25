<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Invoices\InvoiceConsistency;
use App\Invoices\InvoiceMessageFactory;
use App\Invoices\InvoiceOracle;
use App\Invoices\InvoiceReport;
use App\Invoices\InvoiceRunner;
$settings = require __DIR__ . '/bootstrap.php';
$provider = new ProviderFactory($settings)->create();
$root = dirname(__DIR__);
$fixtures = $root . '/fixtures/invoices';
$mode = $argv[1] ?? '';
$names = match ($mode) {
    '' => ['normal.pdf'],
    '--other-layouts' => ['alternate.pdf', 'tax-exempt.pdf'],
    '--review' => ['inconsistent.pdf'],
    '--compare' => ['invoice-full.png', 'invoice-small.png'],
    '--exercise' => ['exercise.pdf'],
    default => [$mode],
};
$report = new InvoiceReport();
$runner = new InvoiceRunner(
    $provider,
    $fixtures,
    new InvoiceMessageFactory(),
    new InvoiceConsistency(),
    InvoiceOracle::fromManifest($fixtures . '/manifest.json'),
    $report,
);
$completed = $runner->run($names);
if ($mode === '--compare') {
    $report->compare();
}
$report->save($root . '/storage/invoices');
echo $completed ? 'Invoice extraction complete.' . PHP_EOL
    : 'Invoice run did not complete.' . PHP_EOL;
exit($completed ? 0 : 1);
