# Exercise: change the extraction task before choosing a size

The demonstration checks the Rivet row. Repeat the comparison for the Bracket row using the same three image variants.

1. Keep the heading and audit-seal checks, and change the expected quantity to the Bracket row's value.
2. Change the question too. An expected value alone must never be sent to the model as evidence.
3. Use one new agent and thread per variant, with the same provider, model and question.
4. Record dimensions, raw bytes, base64 bytes, actual reported input tokens, elapsed seconds and extraction checks.
5. Keep unreadable or incorrect results visible. Select a passing representation only from observed results, and explain why fewer bytes do not necessarily mean fewer tokens or less elapsed time.
6. Check that missing usage prints `unavailable`, while an actual reported zero remains `0`.

## Worked solution

Copy `files/exercise-multimodal-cost.php` to `examples/exercise-multimodal-cost.php` in the completed project. In PhpStorm Terminal, run:

```text
php examples/exercise-multimodal-cost.php
```

The fixture contains heading `HARBOR-731`, audit seal `COVE-592`, Rivet quantity 18 and Bracket quantity 42. The new question asks for the Bracket row and never supplies its answer. The solution runs all three variants, verifies unique thread IDs, checks missing-usage formatting, and requires the full image to pass the distinct task. Lower-resolution failures remain in the table and report instead of becoming successful optimizations.

Expected ending:

```text
Measurement complete.
Distinct Bracket-row task verified.
Fresh threads and missing-usage handling verified.
Exercise complete.
```

Exact accounting, timing and smaller-image correctness can vary. A single passing trial is evidence for that run. It is insufficient to claim reliability across every document or model. The saved local report at `storage/multimodal-cost/latest.json` includes the selected model, exact question, expected and observed values, image fingerprints, dimensions, bytes, usage and timing. A later run replaces that local report, so keep a copy when comparing runs.
