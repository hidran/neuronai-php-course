# Practice: add an optional color

Extend App\Output\Person without changing name, preference, or age.

1. Add a public favoriteColor property. It should hold a string or null and start as null.
2. Add SchemaProperty metadata. The field is optional, its description asks for the color explicitly stated in the source, and the description forbids guessing.
3. Request strings between 2 and 30 characters.
4. Run examples/33-output-class.php again. Predict which existing output line changes.
5. Inspect the generated favoriteColor property and required list using the short check below. Explain why optional and nullable are separate choices, and why the length metadata does not reject an ordinary PHP assignment automatically.

## Worked solution

Add this block before the closing brace of Person:

```php
    #[SchemaProperty(
        description: 'Color explicitly stated. Do not guess.',
        required: false,
        minLength: 2,
        maxLength: 30,
    )]
    public ?string $favoriteColor = null;
```

The existing inspection script now ends with `Schema inspected: 4 properties`. Its other lines stay the same, including the required list `name, preference` and the deliberately assigned age of 10.

Create examples/check-person-color.php and run it in PhpStorm Terminal:

```php
<?php
declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
use App\Output\Person;
use NeuronAI\StructuredOutput\JsonSchema;
$schema = (new JsonSchema())->generate(Person::class);
echo json_encode(
    $schema['properties']['favoriteColor'],
    JSON_PRETTY_PRINT,
) . PHP_EOL;
echo 'Required: ' . implode(', ', $schema['required'])
    . PHP_EOL;
echo 'Default: ' . json_encode((new Person())->favoriteColor)
    . PHP_EOL;
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
```

Optional means the field may be omitted from the data. Nullable means its value may explicitly be null. The default makes reading the property safe even when it was never assigned. A required nullable field would have to be present, but could contain null. The string length bounds are schema metadata; an ordinary PHP assignment checks the declared property type, not those attribute bounds. The application must apply its own validation for the rules it relies on.

Verified with PHP 8.5.0 and NeuronAI 4.1.13 in an isolated project on 2026-10-21. This exercise needs no model request.
