<?php
declare(strict_types=1);
namespace App\Agents;
use App\Dto\Invoice;
class InvoiceAgent extends MediaAgent
{
    protected function getOutputClass(): string
    {
        return Invoice::class;
    }
    // Describe transcription instructions.
}
