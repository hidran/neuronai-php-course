<?php
declare(strict_types=1);
[, $file, $fixture] = $argv + [null, '', ''];
$text = (string) file_get_contents($file);
$report = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
$expected = (require __DIR__ . '/expectations.php')[$fixture];
printf("Parsed one JSON document: score %d, %d findings, %d checks\n",
    $report['score'], count($report['findings']),
    count($report['checks']));
$passed = $total = 0;
$check = function (string $name, bool $ok)
    use (&$passed, &$total): void {
    $total++;
    $passed += $ok ? 1 : 0;
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
};
$check('score follows the rubric',
    $report['score'] === $expected['score']);
foreach ($expected['findings'] as $category => $evidence) {
    $check("{$category} finding cites {$evidence}", array_find(
        $report['findings'],
        fn (array $finding): bool => $finding['category'] === $category
            && in_array($evidence, $finding['evidence'], true),
    ) !== null);
}
$unavailable = array_column(array_filter($report['checks'],
    fn (array $c): bool => $c['status'] === 'unavailable'), 'evidence');
sort($unavailable);
$check('unavailable checks stay unavailable',
    $unavailable === $expected['unavailable']);
echo "Report expectations: {$passed} of {$total} passed\n";
