# Calibrate a metric against a false positive

Our initial keyword assertion accepts a false statement: "Dependency injection means never accepting dependencies." Build a small calibration set before trusting the metric.

1. Keep one correct explanation and this incorrect explanation as separate examples.
2. Confirm that `StringContains('dependencies')` accepts both.
3. Add a `MatchesRegex` check for receiving dependencies from outside. Confirm it accepts the supplied correct example and rejects the false one.
4. Run `php examples/exercise-evaluation.php`. It must print `Evaluation exercise passed.` only after the four positive and negative controls succeed.
5. Write two valid paraphrases that the stronger wording rule rejects. Explain why this metric still does not measure semantic correctness. Propose a human-reviewed criterion before adding a judge.

The included solution is an offline calibration check. It deliberately demonstrates the limits of a stricter wording rule; it does not claim to solve semantic grading. Do not rewrite an evaluation criterion merely to favor one prompt's observed score. Deterministic tests and evaluation datasets serve different purposes.
