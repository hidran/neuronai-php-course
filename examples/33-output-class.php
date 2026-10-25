<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Output\Person;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use NeuronAI\StructuredOutput\JsonSchema;
$schema = JsonSchema::make()->generate(Person::class);
$name = $schema['properties']['name'];
$age = $schema['properties']['age'];
echo 'Name type: ' . $name['type'] . PHP_EOL;
echo 'Name length: ' . $name['minLength'] . ' to '
    . $name['maxLength'] . PHP_EOL;
echo 'Required: ' . implode(', ', $schema['required'])
    . PHP_EOL;
echo 'Age types: ' . implode(' or ', $age['type']) . PHP_EOL;
echo 'Age limits: ' . $age['minimum'] . ' to '
    . $age['maximum'] . PHP_EOL;
$person = new Person(name: 'Ada Turner', preference: 'pizza');
echo 'Default age: ' . json_encode($person->age) . PHP_EOL;
try {
    $person->age = 10;
} catch (Error $error) {
    echo 'Readonly: ' . $error->getMessage() . PHP_EOL;
}
$json = '{"name":"Ada Turner","preference":"pizza","age":32}';
$hydrated = Deserializer::make()->fromJson($json, Person::class);
echo 'Hydrated: ' . $hydrated->name . ', ' . $hydrated->age
    . PHP_EOL;
echo 'Schema inspected: ' . count($schema['properties'])
    . ' properties' . PHP_EOL;
