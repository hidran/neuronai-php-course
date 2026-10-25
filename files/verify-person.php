<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/Person.php';
use App\Output\Person;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use NeuronAI\StructuredOutput\Deserializer\DeserializerException;
use NeuronAI\StructuredOutput\JsonSchema;
$schema = JsonSchema::make()->generate(Person::class);
if ($schema['required'] !== ['name', 'preference']) {
    throw new RuntimeException('required list changed');
}
if (($schema['properties']['age']['minimum'] ?? null) !== 18) {
    throw new RuntimeException('age minimum missing');
}
$json = '{"name":"Ada Turner","preference":"pizza"}';
$person = Deserializer::make()->fromJson($json, Person::class);
if ($person->name !== 'Ada Turner' || $person->age !== null) {
    throw new RuntimeException('hydration failed');
}
try {
    $person->age = 10;
    throw new RuntimeException('readonly not enforced');
} catch (Error) {
}
try {
    Deserializer::make()->fromJson('{"name":"Ada"}', Person::class);
    throw new RuntimeException('missing preference accepted');
} catch (DeserializerException) {
}
echo 'PERSON OK' . PHP_EOL;
