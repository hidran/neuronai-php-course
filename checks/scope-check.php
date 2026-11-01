<?php
declare(strict_types=1);
use NeuronAI\Tools\Toolkits\FileSystem\ReadFileTool;
use NeuronAI\Tools\ToolOutput;
require __DIR__ . '/../vendor/autoload.php';
$read = ReadFileTool::make(__DIR__ . '/../fixtures/scope/inside');
$cases = [
    'notes.txt' => 'readable',
    '../outside.txt' => 'refused',
    'escape.txt' => 'refused',
];
$passed = 0;
foreach ($cases as $path => $expected) {
    $result = $read($path);
    $actual = match (true) {
        is_string($result) => 'readable',
        $result instanceof ToolOutput && $result->isError()
            && str_starts_with($result->getText(), 'Access denied')
            => 'refused',
        default => 'other error',
    };
    $passed += $actual === $expected ? 1 : 0;
    echo ($actual === $expected ? 'PASS ' : 'FAIL ')
        . "{$path}: {$actual}\n";
}
echo "Scope check: {$passed} of " . count($cases) . " passed\n";
