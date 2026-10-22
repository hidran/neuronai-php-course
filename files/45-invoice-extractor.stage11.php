<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Agents\InvoiceAgent;
use App\Invoices\InvoiceLab;
use Dotenv\Dotenv;
Dotenv::createImmutable(dirname(__DIR__))->load();
$lab = new InvoiceLab(dirname(__DIR__));
$extract = static function (string $path) use ($lab): void {
    $source = $lab->read($path);
    $message = $lab->message($source);
    $agent = InvoiceAgent::make()->setThreadId($lab->thread());
    $invoice = $agent->structured($message, maxRetries: 1);
    // Print the auditable result.
};
$lab->run($argv, $extract);
