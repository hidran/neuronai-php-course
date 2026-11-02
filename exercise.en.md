# Exercise: read what Inspector records, and what it does not

Start from the Laravel project at the end of the lesson: the Inspector package installed, `WebRequestMonitoring` in the `api` group, and the `afterResolving` callback that subscribes `InspectorSubscriber(inspector())` to every agent the container builds.

1. Send the second request of `requests/ask.http`, the one with the message missing. Find its `POST /api/ask` transaction in your Inspector dashboard. Write down its result and how many segments its timeline has. Explain why there is no `AssistantAgent` segment and no inference segment: validation fails before the controller ever reaches the agent, so no model call is made.
2. Send the first request again and compare the two transactions: which segment takes most of the time in the successful one?
3. In the controller, temporarily replace the injected agent with one built by hand: `AssistantAgent::make($provider)`, where `$provider` is resolved with `app(AIProviderInterface::class)`. Send the first request. The answer still arrives, but the transaction has no agent segment: an agent that never passes through the container is never subscribed, and nothing warns you. Restore the injected agent.

Acceptance: the invalid request shows a 422 transaction without agent segments; the successful request shows `AssistantAgent` with the inference segment inside it; the hand-built agent answers without any agent segment; after restoring the controller, the agent segments are back.

Without an Inspector account, run the offline check instead: `php verify-endpoint-traces.php <your project>` records the same transactions with a capturing transport and a fake model, and ends with `ENDPOINT TRACES OK`. Never paste your ingestion key into code or into `.env.example`: it belongs in `.env` only.
