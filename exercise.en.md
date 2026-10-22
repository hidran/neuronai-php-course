# Preserve stream boundaries

Build a consumer that handles only nonempty `TextChunk` objects and returns both their exact concatenated text and their count. Make it accept any iterable so that we can check the boundary logic without a provider.

1. Test a word split as `name`, `space`, `s`: the result must be `namespaces`.
2. Test leading whitespace, a chunk containing just one space, and a final newline. Preserve every byte.
3. Test UTF-8 fragments forming `café ☕`. Keep the text intact without treating byte length as token count.
4. In every case, include an empty `TextChunk` and a different object with a `content` property. Neither should contribute text or increase the count.
5. Call `stream()` with a fresh thread and ask about a PHP namespace collision. Show that calling `getReturn()` before iteration fails. Then consume the stream, retrieve its `AgentState`, and check the collected text against its complete message.

Use exceptions for failed checks. Do not rely on PHP's configurable `assert()` behavior. Do not trim fragments or add separators.

Copy the completed solution `files/exercise-stream-boundaries.php` to your project's `examples/` folder. Keep `StreamingAgent.php` in `src/Agents/`, install the project dependencies, and configure the Anthropic provider privately. Run:

```text
php examples/exercise-stream-boundaries.php
```

Expected fixed labels:

```text
Boundary case passed: split word
Boundary case passed: whitespace
Boundary case passed: UTF-8
Live fragments equal final message: yes
Early getReturn rejected: yes
Boundary exercise complete.
```

The three boundary cases use no model requests. The final check uses one real streamed request. Its answer and fragment count vary; correctness is byte-for-byte equality with the final message, plus rejection of an unavailable return value.
