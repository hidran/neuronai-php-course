# Practice: break the parsing boundary

Extend the controlled answers in `examples/32-free-text-parsing.php`. Keep the parser unchanged.

1. Add an answer with a friendly sentence before the valid JSON.
2. Add valid JSON with a name but no total.
3. Add an empty JSON array.
4. Add valid JSON with Ada and an integer total of `1`. Predict every result before running the script.
5. Run the script without `live`. Explain why the last answer is accepted even though the order described in the live question costs 2400 cents.

One possible set of additional entries in `$answers`:

```php
    'Leading' => 'Here is the order: ' . $plain,
    'Missing' => '{"name":"Ada"}',
    'Empty' => '[]',
    'Wrong amount' => '{"name":"Ada","total":1}',
```

Expected additional results:

```text
Leading: invalid JSON
Missing: contract mismatch
Empty: contract mismatch
Wrong amount: accepted
```

The parser checks a small shape contract: an array with a string name and an integer total. It does not check the arithmetic or compare the extracted data with the source. Correct structure and factual correctness need separate checks. Explain one business check the application should run before saving this order.

Verified with PHP 8.5.0 and NeuronAI 4.1.13 in an isolated project, 2026-10-21. No model request is needed for this exercise.
