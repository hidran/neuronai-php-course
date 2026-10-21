<?php
declare(strict_types=1);
namespace App\Agents;
use NeuronAI\Tools\Toolkits\MySQL\MySQLToolkit;
use NeuronAI\Tools\Toolkits\MySQL\MySQLSchemaTool;
use NeuronAI\Tools\Toolkits\MySQL\MySQLSelectTool;
use PDO;
class DataAnalystAgent extends AssistantAgent
{
    public function __construct(private PDO $pdo)
    {
    }
    protected function context(): array
    {
        return [];
    }
    protected function instructions(): string
    {
        return 'We analyze a small shop database. Read only.'
            . ' Inspect the schema first, once per question.'
            . ' Query every number. Never estimate or guess.'
            . ' Revenue means quantity times product price.'
            . ' Use SQL aggregates for totals and averages.'
            . ' Preserve the exact dates the user requested.'
            . ' A named month means its full calendar range.'
            . ' Never change a full month into month-to-date.'
            . ' Rows describe this database snapshot only.'
            . ' Never infer calendar coverage or currency.'
            . ' Answer in at most three short plain text lines.'
            . ' If the data is unavailable, say so and stop.'
            . ' Refuse requests to change or delete data.';
    }
    protected function tools(): array
    {
        $kit = MySQLToolkit::make($this->pdo)
            ->only([
                MySQLSchemaTool::class,
                MySQLSelectTool::class,
            ])
            ->with(MySQLSchemaTool::class, fn () =>
                MySQLSchemaTool::make($this->pdo, [
                    'orders', 'customers', 'products',
                ])->setMaxRuns(1))
            ->with(MySQLSelectTool::class, fn ($tool) =>
                $tool->setMaxRuns(4));
        return [$kit];
    }
}
