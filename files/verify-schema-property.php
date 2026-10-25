<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/PackageFilter.php';
require __DIR__ . '/FindPackagesTool.php';
use App\Dto\PackageFilter;
use App\Tools\FindPackagesTool;
$tool = new FindPackagesTool();
$schema = json_encode($tool->getInputSchema());
foreach (['"vendor"', '"limit"'] as $needle) {
    if (!str_contains($schema, $needle)) {
        throw new RuntimeException("schema lacks {$needle}: {$schema}");
    }
}
// The bounds live on the DTO schema (used by structured output); the tool wrapper forwards names and types.
$dto = json_encode(NeuronAI\StructuredOutput\JsonSchema::make()->generate(PackageFilter::class));
foreach (['"minimum":1', '"maximum":20'] as $needle) {
    if (!str_contains($dto, $needle)) {
        throw new RuntimeException("dto schema lacks {$needle}: {$dto}");
    }
}
$filter = $tool->setInputs(['filter' => ['vendor' => 'psr', 'limit' => 2]])
    ->getInput('filter');
if (!$filter instanceof PackageFilter || $filter->limit !== 2) {
    throw new RuntimeException('filter not deserialized to readonly DTO');
}
$out = (string) $tool($filter);
if (substr_count($out, "\n") > 1 || !str_contains($out, 'psr/')) {
    throw new RuntimeException("unexpected: {$out}");
}
echo 'SCHEMA PROPERTY OK' . PHP_EOL;
