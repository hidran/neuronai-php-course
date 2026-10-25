<?php
declare(strict_types=1);
namespace App\Knowledge;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
use PDO;
final class OrderStatusTool extends Tool
{
    protected string $name = 'order_status';
    protected ?string $description = 'Returns the current status of'
        . ' one order of the signed in customer, from the orders'
        . ' table. Call it before stating any order status.';
    public function __construct(
        private readonly PDO $db,
        private readonly string $customerId,
    ) {
    }
    #[\Override]
    protected function properties(): array
    {
        return [new ToolProperty(
            name: 'order_id',
            type: PropertyType::INTEGER,
            description: 'The order number, like 4471.',
            required: true,
        )];
    }
    public function __invoke(int $order_id): ToolOutput
    {
        if ($order_id < 1) {
            return ToolOutput::error('Order numbers are positive.');
        }
        $query = $this->db->prepare('SELECT status, amount, updated_at'
            . ' FROM orders WHERE id = ? AND customer_id = ?');
        $query->execute([$order_id, $this->customerId]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if ($row === false) {
            return ToolOutput::error("No order {$order_id} for this"
                . ' customer. Do not guess its status.');
        }
        return ToolOutput::text("Order {$order_id} is {$row['status']},"
            . " {$row['amount']} USD, updated {$row['updated_at']}.");
    }
}
