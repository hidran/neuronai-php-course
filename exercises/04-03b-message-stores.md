# Exercise: watch the stores remember

Use the project as the lesson leaves it: `examples/16-message-stores.php` reads the store from `StorageSettings`, and
`MessageStoreFactory` builds it. Run every command in the PhpStorm Terminal, from the project root.

1. Pick a thread name of your own, for example `carol`. Run the script three times on the file store, each time with a
   new sentence, and predict the count before each run:

   ```text
   CHAT_STORE=file php examples/16-message-stores.php carol "Hi, I am Carol."
   ```

   Each run is a new process that adds one question and one answer, so the counts are 2, 4 and 6. List `storage/` and
   find `neuron_carol.json`.
2. Run the same command on thread `CAROL`, in capitals, and read the count. On macOS and Windows the file system ignores
   letter case, so `neuron_CAROL.json` opens `neuron_carol.json`: the count goes on from Carol's conversation. On Linux
   it is a new file and the count starts again at 2. Explain why the lesson keeps thread IDs lowercase.
3. Ask for a store that does not exist:

   ```text
   CHAT_STORE=redis php examples/16-message-stores.php carol "Hi"
   ```

   The factory throws `Unknown CHAT_STORE redis` before any model call, and no `storage/` file changes. Explain why a
   loud failure is better than a quiet fallback to the in-memory store.
4. Run two threads on the SQLite store, `alice` once and `carol` twice, then count the messages per thread, in
   PhpStorm's Database tool window or with the `sqlite3` command line:

   ```text
   sqlite3 storage/chat.sqlite "SELECT thread_id, COUNT(*) FROM chat_messages GROUP BY thread_id;"
   ```

   Expected, if the database held no rows for these threads before: `alice|2` and `carol|4`. One table holds every
   conversation, and the thread ID keeps them apart.

Our own last line, `<store> store, thread <id>: <n> messages`, is the evidence; the model's answers vary. Step 3 runs
without a model call. Checked offline on 2026-10-25 with PHP 8.5 and NeuronAI 4.1.13: the unknown store error and the
case behavior of the file store on macOS (thread `ALICE` loads the two messages written on `alice`).
