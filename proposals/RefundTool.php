<?php
declare(strict_types=1);
namespace App\Neuron\Tools;
use NeuronAI\Tools\Tool;
// approvalPolicy() as an older neuron-tool skill shows it.
final class RefundTool extends Tool
{
    protected string $name = 'refund_order';
    protected ?string $description = 'Refunds an order.';
    #[\Override]
    protected function properties(): array
    {
        return [];
    }
    #[\Override]
    protected function approvalPolicy(array $inputs): bool|string
    {
        return 'Refunds always need a human decision.';
    }
    public function __invoke(): string
    {
        return 'Refund requested.';
    }
}
