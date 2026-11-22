# Exercise: prove instance-level monitoring

Build two Observed Weather Agents from the same provider, each with its own fresh thread identifier. Subscribe a counter for `ObservabilityEvent` to the first instance only. Subscribe a second listener for `ToolCalled` to both instances and print the completed tool names. Ask both agents the same fixture question (the temperatures of Turin and Milan and their average with the mean tool).

Record which listeners fire. Explain why a correct answer from the second agent does not demonstrate monitoring coverage: nothing was attached to that instance for the broad event family. Compare the broad event count with the completed tool count. Do not assert exact answer wording or a fixed number of inference requests.

Acceptance: both agents return an answer, the broad counter grows during the first run and stays unchanged during the second, and each run reports at least three completed tool calls (one per city and the mean). The runnable solution is `examples/exercise-observability.php`; it uses a real model and name-only counters and ends with `Instance monitoring exercise passed.`

Backend part (needs an Inspector account and `INSPECTOR_INGESTION_KEY` in your private `.env`): run `php examples/49-observability.php inspector`, which attaches `AgentMonitor` with the `InspectorSubscriber` to the executed instance. Find the transaction whose request carries the printed run label and compare its completed tool segments with the `Completed tool:` lines in your terminal. Without an Inspector account, complete the local part and mark backend correlation as unverified. An empty dashboard never proves that no agent ran.
