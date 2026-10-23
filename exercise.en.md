# Exercise: prove instance-level monitoring

Build two weather agents with separate fresh thread identifiers. Attach a counter for ObservabilityEvent to only the first instance. Run both with the same fixture question. Record which listener fires and explain why a successful answer from the second agent does not demonstrate monitoring coverage.

Subscribe a second counter specifically to ToolCalled on both agents and record completed tool names. Compare the broad event count with the narrow count. Do not assert exact answer wording or a fixed count of inference requests. Then construct the second agent through MonitoredAgentFactory and repeat with real Inspector configuration.

Acceptance: both agents return answers, only the subscribed broad listener receives its instance's events, the completed-tool listener reports actual calls, and the factory returns the monitored instance that the caller executes. Match at least one real Inspector transaction to the fresh thread identifier and teaching question. Without an Inspector account, complete the local part and explicitly mark backend correlation unverified. Never claim an empty dashboard proves no agent ran.

The runnable local solution is examples/exercise-observability.php. It uses a real model and name-only counters. Backend acceptance requires the real dashboard, independently of this script's success.
