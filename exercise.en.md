# Exercise: change the extraction task before choosing a size

The demonstration checks the Rivet row. Repeat the comparison for the Bracket row using the same three image variants.

1. Keep the heading and audit-seal checks, and change the expected quantity to the Bracket row's value.
2. Change the question too: the manifest already holds `exercise_question`. An expected value alone must never be sent to the model as evidence.
3. Reuse `BenchmarkRunner` and `BenchmarkReport` unchanged: one new agent and thread per variant, the same provider, model and `CostProbe` class.
4. Record dimensions, raw bytes, base64 bytes, reported input tokens, elapsed seconds and extraction checks.
5. Keep unreadable or incorrect results visible. Select a passing representation only from observed results, and explain why fewer bytes do not necessarily mean fewer tokens or less elapsed time.
6. Check that every request used its own thread, that the full image passes the new task, and that the measurement units make sense (base64 is never smaller than the raw file).

## Worked solution

Copy `files/exercise-multimodal-cost.php` to `examples/exercise-multimodal-cost.php` in the completed project. In PhpStorm Terminal, run:

```text
php examples/exercise-multimodal-cost.php
```

The fixture contains heading `HARBOR-731`, audit seal `COVE-592`, Rivet quantity 18 and Bracket quantity 42. The solution builds a `BenchmarkRunner` with `$fixtures->exerciseQuestion` and the Bracket expectations, prints the same report, then verifies unique thread IDs and the units of every row. It finds the full row with `array_find`, available since PHP 8.4 and part of our PHP 8.5 baseline, and requires it to pass. Lower-resolution failures stay in the table and in the saved report instead of becoming successful optimizations.

Expected ending:

```text
Measurement complete.
Distinct Bracket row task verified.
Fresh threads and measurement units verified.
Exercise complete.
```

Exact accounting, timing and smaller-image correctness can vary. A single passing trial is evidence for that run, not a reliability guarantee across documents or models. The saved local report at `storage/multimodal-cost/latest.json` includes the provider, model, threads, dimensions, bytes, usage, timing, observed probes and checks. A later run replaces it, so keep a copy when comparing runs.
