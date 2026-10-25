# Exercise: extract a nested delivery profile

Use the completed Address, Tag and Delivery contracts and PersonExtractor.

1. Extract from: "I am Mia Chen. Deliver to 7 Cedar Road, ZIP 00560. Tags: office with priority 2, cold with priority 1. Keywords: recyclable."
2. Wire the extractor as in the lesson: `bootstrap.php` returns the settings, `ProviderFactory` builds the provider, and `PersonExtractor::make($provider)` receives it. Pass Delivery::class explicitly to structured(), overriding the extractor's Person default.
3. Verify that the result is a Delivery, its address is an Address, and every tags item is a Tag.
4. Check the stated name, street, ZIP, tags with their priorities and keywords. The ZIP must remain the string "00560", the missing city must remain null and each priority must be an integer.
5. Explain why anyOf and ArrayOf do different jobs, and why the priority's min and max are not a validation rule. Explain why keywords needs no object item class.

## Worked solution

Copy `files/exercise-nested-objects.php` to `examples/exercise-nested-objects.php` in the completed project, then run it in PhpStorm Terminal:

```text
php examples/exercise-nested-objects.php
```

The solution checks all specified facts and runtime classes, collects the tags by name, sorts them to avoid assuming the provider's ordering, and throws if an expected check fails.

```text
Result: App\Output\Delivery
Address type: App\Output\Address
ZIP: 00560
City: not stated
Tag: cold, priority 1
Tag: office, priority 2
Keywords: recyclable
Exercise complete.
```

anyOf supplies the object item class to the schema generator and deserializer. ArrayOf validates each hydrated item and its declared rules. allowEmpty: true permits no tags; required: true still requires the tags key. min and max on the priority become a minimum and a maximum in the generated schema: they guide the model, but no validation rule enforces them. A plain array without anyOf becomes an array of strings in NeuronAI's generated schema. PHP's array type itself does not enforce the element class or scalar type.
