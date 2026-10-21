<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Output\Person;
use NeuronAI\StructuredOutput\JsonSchema;
$schema = (new JsonSchema())->generate(Person::class);
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
$person = new Person();
echo 'Default age: ' . json_encode($person->age) . PHP_EOL;
$person->age = 10;
echo 'Assigned age: ' . $person->age . PHP_EOL;
echo 'Schema inspected: ' . count($schema['properties'])
    . ' properties' . PHP_EOL;
