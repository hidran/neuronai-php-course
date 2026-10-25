# Verify a second office through the local server

Use the reviewed PHP stdio server and the `McpConnection` and `OfficeHoursAgent` classes from the lesson. Keep stdout
restricted to newline-delimited JSON-RPC responses. Do not add a debug echo or a second tool to make the checks pass.

1. Build one connection with `McpConnection::local(...)->only(['get_office_hours'])` and check that it discovers exactly one
   tool before involving a model.
2. Invoke the south office directly through the connection's connector. Check the exact returned location, hours
   `10:00-18:00` and timezone `Europe/Rome`.
3. Call an unknown location and a non-string location. Both must return a tool result marked as an error, rather than an
   invented schedule.
4. Restrict another connection to a misspelled tool name. It must discover an empty list. An application should reject that
   condition before inference.
5. Inject the same connector into `OfficeHoursAgent`, ask about the south office in a fresh thread and require a completed
   `get_office_hours` call in the streaming view. Check the call, not the wording of the answer.

Copy `files/exercise-local-mcp.php` into the project's `examples/` folder and run:

```text
php examples/exercise-local-mcp.php
```

Expected evidence:

```text
Direct south invocation and error paths: passed
Misspelled allowlist discovered no tools: passed
[actual call ID, location input, matching result and grounded answer]
Tool stream complete.
South office grounded in an actual tool result: passed
Local MCP exercise complete.
```

The direct checks use the actual local child process without a model request. The last check uses the normal agent workflow
and the existing streaming view; the provider comes from `NEURON_PROVIDER` in `.env`. Explicit exceptions enforce the checks
independently of PHP assertion configuration. A tool's read-only annotation is metadata; the reviewed implementation and
operating permissions determine what the child process can actually do.
