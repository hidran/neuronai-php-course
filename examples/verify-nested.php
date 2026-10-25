<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/Address.php';
require __DIR__ . '/Tag.php';
require __DIR__ . '/Delivery.php';
use App\Output\Address;
use App\Output\Delivery;
use App\Output\Tag;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use NeuronAI\StructuredOutput\JsonSchema;
use NeuronAI\StructuredOutput\Validation\Validator;
$schema = JsonSchema::make()->generate(Delivery::class);
$tags = $schema['properties']['tags']['items'];
if (($tags['properties']['priority']['maximum'] ?? null) !== 3) {
    throw new RuntimeException('tag priority limits missing');
}
$keywords = $schema['properties']['keywords']['items'];
if ($keywords !== ['type' => 'string']) {
    throw new RuntimeException('keywords must be strings');
}
$json = '{"name":"Ada","address":{"street":"12 Maple","zip":"02108"},'
    . '"tags":[{"name":"urgent","priority":1}],"keywords":["x"]}';
$delivery = Deserializer::make()->fromJson($json, Delivery::class);
if (!$delivery->address instanceof Address
    || $delivery->address->city !== null
    || !$delivery->tags[0] instanceof Tag
    || $delivery->tags[0]->priority !== 1) {
    throw new RuntimeException('nested hydration failed');
}
$blank = new Delivery('Ada', new Address('', '02108'), [], ['x']);
if (Validator::validate($blank) === []) {
    throw new RuntimeException('nested NotBlank not enforced');
}
echo 'NESTED OK' . PHP_EOL;
