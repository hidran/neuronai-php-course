# Exercise: keep every failure category honest

The extractor is complete. Now prove, without spending a model request, that each kind of problem ends in the right place, then run a distinct invoice for real.

1. Build an `Invoice` by hand with the date `2026-02-30`, a subtotal of 1300 cents and a single line of 2 x 500 = 1000 cents. Show that `InvoiceConsistency` reports exactly two issues: the calendar date and the subtotal.
2. Deserialize an invoice JSON whose currency is `ZZZ` with NeuronAI's `Deserializer`. Explain why it fails before any validation rule runs.
3. Give `InvoiceRunner` a `FakeAIProvider` whose replies are not JSON, and run it on `missing.pdf` and `normal.pdf`. Check that the first is a `local_file_problem` and the second a `validation_failure`, and that the run reports it did not complete.
4. With `--live`, extract the fictional `exercise.pdf` and require `accepted`: invoice `EX/0073`, Willow Bench Supply, `2026-10-18`, EUR, subtotal 3700, tax 740, total 4440 cents; Spacer set 4 x 375 = 1500 and Steel guide 2 x 1100 = 2200.

## Worked solution

Copy `files/exercise-invoice-extractor.php` to `examples/exercise-invoice-extractor.php` in the completed project. In the PhpStorm terminal run:

```text
php examples/exercise-invoice-extractor.php
```

Expected output (offline, no provider call):

```text
Local review: calendar date and subtotal verified.
Currency enum: ZZZ rejected before validation.
Failure categories: local file and validation kept apart.
Live extraction skipped. Add --live to run it.
Exercise complete.
```

Then run `php examples/exercise-invoice-extractor.php --live`. It extracts `exercise.pdf` with the provider from `.env`, saves the report and reads `storage/invoices/latest.json` back, requiring the state `accepted`, the number `EX/0073` and the total 4440. It ends with `Distinct source: every mandatory field verified.` and `Exercise complete.`

Why the two checks differ: the date matches the regular expression, because a shape is not a calendar, so only our consistency check catches it. The currency cannot even become a `Currency` enum case, so the reply fails at deserialization; inside `structured()` that uses the single retry and then becomes a `validation_failure`. A missing file never reaches the provider at all.
