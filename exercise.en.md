# Exercise: verify the streaming command line boundary

Run the completed CLI runner with a new question: "Explain when a PHP interface is useful in testing."

1. Pass the entire question as one quoted command line argument.
2. Confirm that `You:` contains the caller's question and `Assistant:` appears before the answer.
3. Confirm that answer text becomes readable before the completion metrics are printed.
4. Check that the first-text duration is nonnegative and does not exceed total duration.
5. Compare the displayed answer's character count with the final state's reported count. Explain why that count is not a token count.

## Worked solution

Copy `files/exercise-streaming-cli.php` to `examples/exercise-streaming-cli.php` in the completed project. Open PhpStorm Terminal and run:

```text
php examples/exercise-streaming-cli.php
```

The solution starts the existing CLI runner directly with an argument array, reads its stdout while it is running, and forwards each read immediately. It checks the caller prompt, output label order, timing format and ordering, nonempty answer, and character count. It also requires a read containing answer text before the metrics arrive. This is observable delivery evidence for this run, rather than a promise about future runs or a controlled performance comparison. A pipe can combine multiple provider fragments into one read, so the reader never treats a pipe read as a token or a provider chunk.

Expected ending:

```text
First text: [seconds]s | Total: [seconds]s
Final text: [count] characters
CLI stream complete.
Caller prompt preserved: yes
Output length matches final state: yes
Text observed before completion report: yes
Exercise complete.
```

The answer, durations and count vary. The solution asserts their relationships instead of exact provider wording. The runner owns the completion marker; its presence alone is insufficient if an exception or a failed check occurs. If the exercise cannot observe early text, inspect active output buffers and the consumer rather than claiming the display streamed. First text can round to zero for a sufficiently quick response, and first and total can round to the same value.
