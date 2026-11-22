# Practice: add an optional color

Extend App\Output\Person without changing name, preference, or age.

1. Add a promoted constructor parameter favoriteColor. It should hold a string or null and default to null.
2. Add SchemaProperty metadata. The field is optional, its description asks for the color explicitly stated in the source, and the description forbids guessing.
3. Request strings between 2 and 30 characters.
4. Run examples/33-output-class.php again. Predict which existing output line changes.
5. Inspect the generated favoriteColor property and required list using the short check below, and hydrate a Person from JSON that leaves the color out. Explain why optional and nullable are separate choices, and why the length metadata does not reject a short color passed to the constructor.

## Worked solution

Add this block after the age parameter, before the closing parenthesis of the constructor:

```php
        #[SchemaProperty(
            description: 'Color explicitly stated. Do not guess.',
            required: false,
            minLength: 2,
            maxLength: 30,
        )]
        public ?string $favoriteColor = null,
```

The existing inspection script now ends with `Schema inspected: 4 properties`. Its other lines stay the same, including the required list `name, preference` and the readonly refusal.

Create examples/check-person-color.php and run it in PhpStorm Terminal:

```php
<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Output\Person;
use NeuronAI\StructuredOutput\Deserializer\Deserializer;
use NeuronAI\StructuredOutput\JsonSchema;
$schema = JsonSchema::make()->generate(Person::class);
echo json_encode(
    $schema['properties']['favoriteColor'],
    JSON_PRETTY_PRINT,
) . PHP_EOL;
echo 'Required: ' . implode(', ', $schema['required'])
    . PHP_EOL;
$json = '{"name":"Ada Turner","preference":"pizza"}';
$person = Deserializer::make()->fromJson($json, Person::class);
echo 'Default: ' . json_encode($person->favoriteColor) . PHP_EOL;
$short = new Person(name: 'Ada', preference: 'tea', favoriteColor: 'x');
echo 'Short color: ' . $short->favoriteColor . PHP_EOL;
```

Expected output:

```json
{
    "description": "Color explicitly stated. Do not guess.",
    "default": null,
    "type": [
        "string",
        "null"
    ],
    "minLength": 2,
    "maxLength": 30
}
```

```text
Required: name, preference
Default: null
Short color: x
```

Optional means the field may be omitted from the data. Nullable means its value may explicitly be null. The default gives the object a safe value when the JSON or the caller leaves the color out. A required nullable field would have to be present, but could contain null. The string length bounds are schema metadata: the constructor checks the declared type, not those attribute bounds, so a one-letter color is accepted. The application must apply its own validation for the rules it relies on.

This exercise needs no model request.
