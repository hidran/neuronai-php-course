# Correlate repeated tool invocations

Check the tool progress consumer against streams you build yourself: one invocation must stay distinct from another, raw tool payloads must stay out of the user display, and an interrupted answer must report why it stopped.

Use the existing `ServerConfigurationTool`, `ServerAgent`, `StreamProgress` and `ToolStreamConsumer`. No new execution interface is needed.

1. Build a deterministic stream with two calls of the same capability and different call IDs. Return their results in reverse order. Check that both invocations complete under their own IDs, in the order their results arrived.
2. Give the calls an unknown internal tool name and obvious sentinel values in their inputs and results. The user display must show the generic fallback twice. It must reveal neither internal names, nor payloads, nor partial arguments, and its summary line must read `Tool runs: 2, pending: 0`.
3. Include a partial `ToolArgumentChunk` and an unrelated object with a `content` property. Neither may become answer text or trigger any execution. Replay the same stream through the diagnostic view and check that it previews the partial arguments.
4. Simulate a provider failure after half a sentence by throwing a `ProviderException`. Keep the partial text visible, report that the answer did not finish together with the reason, and return no state. Print no success label.
5. Optionally, make one real request asking for both training and backup addresses. Require two distinct call IDs for the same capability and both fixture addresses in the final answer.

Use explicit exceptions for failed checks rather than PHP's configurable `assert()`. Synthetic payloads are fine in the deterministic cases. Never use real credentials as test payloads.

Copy `files/exercise-tool-progress.php` to the project's `examples/` folder and run:

```text
php examples/exercise-tool-progress.php
```

Expected output, with no provider request:

```text
Repeated name and reverse results: passed
Unknown names and raw payload policy: passed
Partial arguments previewed in diagnostics: passed
Partial failure without false success: passed
Live stream skipped. Add --live to run it.
Tool progress exercise complete.
```

With `php examples/exercise-tool-progress.php --live`, the four deterministic checks are followed by the allowlisted progress, an answer with both addresses, the summary and completion lines, then:

```text
Two live invocations have distinct IDs: passed
Tool progress exercise complete.
```

This policy controls progress output. It does not redact generated answer text or replace tool authorization and data-access rules.
