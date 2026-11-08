# Project rules for coding assistants
PHP 8.5 and neuron-core/neuron-ai 4.1: read signatures in vendor/.
Agents extend NeuronAI\Agent\Agent. chat() returns AgentState.
stream() returns a Generator: getReturn() is the final state.
Bind a thread with for() before chat(). Tools are named classes.
Thread IDs come from the server, never from user input.
Validate input where it enters: the CLI or the HTTP layer.
Never print, log or commit keys, and never open .env files.
Test with FakeAIProvider first, then one live smoke check.
