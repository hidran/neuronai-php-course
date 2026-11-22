# Building an invoice extractor

Source code of the lesson "Building an invoice extractor" of Agentic AI in PHP with NeuronAI.

This branch is the course project at the end of the lesson. It contains everything the earlier lessons built, and its last commit adds only what this lesson changes after "Measuring multimodal cost": compare it with the parent commit to see the lesson's work.

## Setup

Use PHP 8.5 or later.

```bash
composer install
cp .env.example .env
```

Put your own provider keys in `.env` and choose the provider with `NEURON_PROVIDER`. `.env` is never committed.

## What this lesson changes

- `examples/45-invoice-extractor.php`
- `examples/exercise-invoice-extractor.php`
- `fixtures/invoices/alternate.pdf`
- `fixtures/invoices/alternate.png`
- `fixtures/invoices/build-fixtures.py`
- `fixtures/invoices/exercise.pdf`
- `fixtures/invoices/exercise.png`
- `fixtures/invoices/inconsistent.pdf`
- `fixtures/invoices/inconsistent.png`
- `fixtures/invoices/invoice-full.png`
- `fixtures/invoices/invoice-small.png`
- `fixtures/invoices/manifest.json`
- `fixtures/invoices/normal.pdf`
- `fixtures/invoices/normal.png`
- `fixtures/invoices/tax-exempt.pdf`
- `fixtures/invoices/tax-exempt.png`
- `src/Agents/InvoiceAgent.php`
- `src/Invoices/Currency.php`
- `src/Invoices/Invoice.php`
- `src/Invoices/InvoiceConsistency.php`
- `src/Invoices/InvoiceLine.php`
- `src/Invoices/InvoiceMessageFactory.php`
- `src/Invoices/InvoiceOracle.php`
- `src/Invoices/InvoiceReport.php`
- `src/Invoices/InvoiceRunner.php`
- `src/Invoices/InvoiceSource.php`

## Run

- `php examples/45-invoice-extractor.php`
- `php examples/exercise-invoice-extractor.php`
