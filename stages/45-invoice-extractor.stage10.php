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
    // Request a typed invoice.
    // Print the auditable result.
};
$lab->run($argv, $extract);
