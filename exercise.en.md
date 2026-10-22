# Exercise: extract a new invoice and keep review explicit

Use the fictional `exercise.pdf` invoice. Preserve its identifier, supplier, normalized date, currency, totals and both ordered line items. Check every source field, then run the same deterministic arithmetic policy.

1. Expect invoice `EX/0073`, Willow Bench Supply, `2026-10-18`, EUR subtotal 37.00, tax 7.40 and total 44.40.
2. Verify Spacer set (quantity 4, unit 3.75, line 15.00) and Steel guide (quantity 2, unit 11.00, line 22.00).
3. Preserve the actual source fingerprint outside the DTO and require all source checks to pass.
4. Test a hand-built subtotal disagreement and zero-quantity nested line independently of inference.
5. Change a local DTO's date to `2026-02-30` and currency to `ZZZ`. Explain why matching the field regex is insufficient.
6. Prove the application distinguishes validation failure from local/provider request failure without spending another model request.

## Worked solution

Copy `files/exercise-invoice-extractor.php` to `examples/exercise-invoice-extractor.php` in the completed project. In PhpStorm Terminal run:

```text
php examples/exercise-invoice-extractor.php
```

The solution sends the distinct PDF through a fresh structured request with at most one retry. It compares all mandatory fields and both rows with the fingerprint-bound fixture facts, requires accepted status, executes the direct checker and nested ArrayOf tests, checks real-date/currency review, and invokes explicit local failure probes. The probes do not contact a provider and are identified as tests. They verify application status classification rather than claiming a live API failed.

Expected ending:

```text
Direct checker review: subtotal mismatch verified.
Nested ArrayOf validation: rejected zero quantity.
Invoice extraction complete.
Distinct source: every mandatory field verified.
Calendar, currency and failure states verified.
Exercise complete.
```

Answer wording, reported usage and timings can vary. Source values, ordered rows and status relationships are checked explicitly. A valid DTO with a source mismatch requires review; it is not approved because its total happens to match. The pure checker applies this lab's two-decimal row rounding and one cent tolerance. It is a documented teaching policy, not a universal financial rule.
