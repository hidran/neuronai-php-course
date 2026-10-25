# Exercise: a stricter, more transparent persistent chat

Use the completed persistent chat project: `assistant.php` with its message store, the thread as the second argument, `/history` and `/reset`.

1. Run `ANTHROPIC_MODEL=does-not-exist php assistant.php "Hi"` in argument mode. The error line is the same as in the chat, but the exit code is 0 (`echo $?`). Make `ask()` return whether it succeeded, and exit with code 1 in argument mode when it did not. The chat loop keeps going after a failed call, as before.
2. Add a `CHAT_WINDOW` setting, read in `StorageSettings::fromEnv()` next to the other storage settings, and pass it to `setContextWindow()` when `assistant.php` builds the agent. Set it to one thousand tokens, chat for four turns on a fresh thread, and watch `/history` show fewer messages sent than stored.
3. Make `/history` also print the role and the first forty characters of every stored message, one line each.

Expected behavior:

```text
$ ANTHROPIC_MODEL=does-not-exist php assistant.php "Hi"; echo $?
Model error: HTTP 404 ...
1
```

After the fourth turn with a small window, the counts differ: the store still holds every message, archived ones included, while the model receives only the active part of the thread. `/reset` still clears the whole thread, archived messages included.
