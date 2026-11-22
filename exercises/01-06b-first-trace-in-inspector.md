# Exercise: trace two providers and compare

You need a free Inspector account and your ingestion key in `.env` as `INSPECTOR_INGESTION_KEY` (never in `.env.example`).

1. Run the traced hello script twice, once with your default provider and once with another one you have a key for:

   ```
   php examples/01b-hello-inspector.php
   NEURON_PROVIDER=openai php examples/01b-hello-inspector.php
   ```

2. In the Inspector dashboard, open the Agents view and find the two runs of `App\HelloAgent`. For each run, note the total
   duration, the duration of the `inference( UserMessage )` segment and the input and output tokens.
3. Change the question in the script to one that needs a longer answer, run it again with the same provider, and compare the
   inference duration and the output tokens with the first run.
4. Remove `INSPECTOR_INGESTION_KEY` from your `.env` (or leave it empty) and run the script once more: it must stop with
   `INSPECTOR_INGESTION_KEY is not set` before any model call. Put the key back.

Acceptance: every successful run prints `Trace name in Inspector: App\HelloAgent` and appears in the Agents view with
`AgentStartNode`, `ChatNode`, one inference segment, two `save_message` segments and `AgentEndNode`; the run without a key
fails with the message from `MonitoringSettings`. Write down which part of the run took most of the time and how the token
counts changed with the longer answer. Model answers and timings vary: compare them, do not expect fixed numbers.
