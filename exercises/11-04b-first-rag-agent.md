# Index and retrieve a separate dispatch policy

Build a second tiny knowledge base without touching the return-policy collection.

1. Start from `$rag = require __DIR__ . '/rag-bootstrap.php';` and load `fixtures/rag/dispatch-policy.txt` with `FileDataLoader`. Set each document's source name to the bare file name.
2. Make a `ReturnsPolicyAgent` with the services from `$rag` and a store from `$rag->stores->create('dispatch')`, and index the documents with `reindexBySource`. Run the script twice: the collection must still hold one chunk.
3. Discard that agent. Make a fresh agent on the same collection, as a new process would, and call `resolveRetrieval()->retrieve()` with "How quickly does an order leave the warehouse?".
4. Check that the first hit's source name is `dispatch-policy.txt`, that its content contains `3 business days`, and print the dimensions of its stored vector. Inspect this evidence before adding any chat call.

The solution is `examples/exercise-first-rag.php`; it prints `First RAG exercise passed.` only after those checks succeed. Then explain why a fluent answer about shipping is not evidence that the right source was retrieved, and add an unsupported question to see what the documents can and cannot answer.
