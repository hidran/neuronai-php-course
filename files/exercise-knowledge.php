<?php
declare(strict_types=1);
// Exercise solution: counts are records too. Run it with "ask" to let a model use the tool.
require __DIR__ . '/../vendor/autoload.php';
use App\Config\ProviderFactory;
use App\Knowledge\OrderDatabase;
use NeuronAI\Agent\Agent;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
$settings = require __DIR__ . '/bootstrap.php';
final class RefundCountTool extends Tool
{
    protected string $name = 'refund_count';
    protected ?string $description = 'Counts the refunded orders of the'
        . ' signed-in customer in one calendar month, from the orders table.';
    public function __construct(
        private readonly PDO $db,
        private readonly string $customerId,
    ) {
    }
    #[\Override]
    protected function properties(): array
    {
        return [new ToolProperty(name: 'month', type: PropertyType::STRING,
            description: 'A month as YYYY-MM, like 2026-10.', required: true)];
    }
    public function __invoke(string $month): ToolOutput
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) !== 1) {
            return ToolOutput::error('Use a month like 2026-10.');
        }
        $query = $this->db->prepare("SELECT COUNT(*) FROM orders"
            . " WHERE customer_id = ? AND status = 'refunded'"
            . " AND substr(updated_at, 1, 7) = ?");
        $query->execute([$this->customerId, $month]);
        $count = (int) $query->fetchColumn();
        return ToolOutput::text("Refunded orders in {$month}: {$count}.");
    }
}
$table = [
    'short stable text' => 'direct context',
    'document' => 'retrieval',
    'record' => 'tools',
    'learned behavior' => 'fine tuning',
];
$requests = [
    ['What warranty does the Cedar Desk Lamp have?', 'short stable text'],
    ['How many refunds did I get in October 2026?', 'record'],
    ['Always answer in our support team\'s voice.', 'learned behavior'],
];
foreach ($requests as [$question, $authority]) {
    printf("%s\n  authority %s -> %s\n", $question, $authority,
        $table[$authority] ?? 'no mechanism yet');
}
$db = new OrderDatabase(dirname(__DIR__)
    . '/storage/knowledge/orders.sqlite')->pdo();
$expected = ['C-204' => 2, 'C-118' => 1];
foreach ($expected as $customer => $count) {
    $text = (new RefundCountTool($db, $customer))(month: '2026-10')->getText();
    printf("refund_count as %s: %s\n", $customer, $text);
    if ($text !== "Refunded orders in 2026-10: {$count}.") {
        throw new RuntimeException("Wrong count for {$customer}");
    }
}
if (!(new RefundCountTool($db, 'C-118'))(month: 'October')->isError()) {
    throw new RuntimeException('An invalid month must be rejected');
}
echo 'Invalid month rejected.' . PHP_EOL;
if (($argv[1] ?? '') === 'ask') {
    $tool = new RefundCountTool($db, 'C-204');
    $provider = new ProviderFactory($settings)->create();
    $state = Agent::make()->setAiProvider($provider)
        ->setInstructions(new SystemMessage('Answer counts only from'
            . ' the refund_count tool. Today is 2026-10-24. One sentence.'))
        ->addTool($tool)->setThreadId('exercise-refunds')
        ->chat(new UserMessage('How many refunds did I get this month?'));
    echo 'Model: ' . $state->getMessage()?->getContent() . PHP_EOL;
    $calls = $state->getToolRuns('refund_count');
    echo "refund_count calls: {$calls}" . PHP_EOL;
    if ($calls < 1) {
        throw new RuntimeException('The model answered without counting');
    }
}
echo 'Knowledge exercise complete.' . PHP_EOL;
