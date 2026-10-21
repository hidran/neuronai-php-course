# Exercise: extract a nested delivery profile

Use the completed Address, Tag and Delivery contracts and PersonExtractor.

1. Extract from: "I am Mia Chen. Deliver to 7 Cedar Road, ZIP 00560. Tags: office, cold. Keywords: recyclable."
2. Pass Delivery::class explicitly to structured(), overriding the extractor's Person default.
3. Verify that the result is a Delivery, its address is an Address, and every tags item is a Tag.
4. Check the stated name, street, ZIP, tags and keywords. The ZIP must remain the string "00560" and the missing city must remain null.
5. Explain why anyOf and ArrayOf do different jobs. Explain why keywords needs no object item class.

## Worked solution

Copy `files/exercise-nested-objects.php` to `examples/exercise-nested-objects.php` in the completed project, then run it in PhpStorm Terminal:

```text
php examples/exercise-nested-objects.php
```

The solution checks all specified facts and runtime classes, sorts tag names to avoid assuming the provider's ordering, and throws if an expected check fails.

```text
Result: App\Output\Delivery
Address type: App\Output\Address
ZIP: 00560
City: not stated
Tags: cold, office
Keywords: recyclable
Exercise complete.
```

anyOf supplies the object item class to the schema generator and deserializer. ArrayOf validates each hydrated item and its declared rules. allowEmpty: true permits no tags; required: true still requires the tags key. A plain array without anyOf becomes an array of strings in NeuronAI's generated schema. PHP's array type itself does not enforce the element class or scalar type.
