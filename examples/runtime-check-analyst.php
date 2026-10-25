<?php
declare(strict_types=1);
// Runs on the studio Mac against the analyst MariaDB container (not offline).
require __DIR__ . '/../vendor/autoload.php';
use NeuronAI\Tools\Toolkits\MySQL\MySQLSchemaTool;
use NeuronAI\Tools\Toolkits\MySQL\MySQLSelectTool;
use NeuronAI\Tools\Toolkits\MySQL\MySQLToolkit;
require __DIR__ . '/bootstrap.php';
$pdo = require __DIR__ . '/database.php';
$denied = function (string $sql, string $label) use ($pdo) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $error) {
        if (($error->errorInfo[1] ?? 0) !== 1142) {
            throw $error;
        }
        echo $label . ' DENIED by server' . PHP_EOL;
        return;
    }
    throw new RuntimeException($label . ' was allowed');
};
$denied('DELETE FROM orders WHERE id = -1', 'DELETE');
$denied('UPDATE orders SET quantity = 1 WHERE id = -1', 'UPDATE');
$denied('SELECT * FROM internal_notes', 'PRIVATE TABLE');
$names = $pdo->query('SELECT name FROM products')->fetchAll(PDO::FETCH_COLUMN);
if (!array_any($names, fn ($n) => str_contains($n, 'SYSTEM:'))) {
    throw new RuntimeException('Injection fixture row missing');
}
$tables = ['orders', 'customers', 'products'];
$schema = MySQLSchemaTool::make($pdo, $tables)->__invoke();
foreach ($tables as $table) {
    if (!str_contains($schema, "### Table: `{$table}`")) {
        throw new RuntimeException('Schema table missing');
    }
}
if (str_contains($schema, 'internal_notes')) {
    throw new RuntimeException('Private table leaked');
}
$kit = MySQLToolkit::make($pdo)->only([
    MySQLSchemaTool::class, MySQLSelectTool::class,
]);
$names = array_map(fn ($tool) => $tool->getName(), $kit->tools());
echo 'Tools: ' . implode(', ', $names) . PHP_EOL;
if (count($names) !== 2 || in_array('mysql_write_query', $names)) {
    throw new RuntimeException('Unexpected toolkit tools');
}
$sql = 'SELECT COUNT(*) AS orders, '
    . 'SUM(o.quantity * p.price) AS revenue '
    . 'FROM orders o JOIN products p ON p.id = o.product_id '
    . "WHERE o.ordered_at >= :start AND o.ordered_at < :end";
$rows = MySQLSelectTool::make($pdo)->__invoke($sql, [
    ['name' => 'start', 'value' => '2026-10-01'],
    ['name' => 'end', 'value' => '2026-11-01'],
]);
if (!is_array($rows) || (int) $rows[0]['orders'] !== 4
    || (string) $rows[0]['revenue'] !== '560.00') {
    throw new RuntimeException('Unexpected October totals');
}
echo 'October: 4 orders, revenue 560.00' . PHP_EOL;
if ($pdo->inTransaction()) {
    throw new RuntimeException('Tool left a transaction open');
}
echo 'READ ONLY VERIFIED' . PHP_EOL;
