# Exercise: prove what mutates

Start from the Laravel project at the end of the lesson: `diagnostics/copies.php` prints the thread label and the tool names of the definition, Alice's copy and Bob's copy.

1. Add a branch for the argument `repoint`: call `$aliceAgent->setThreadId('demo-bob');`. Run `php diagnostics/copies.php repoint` and read the refusal: a bound instance cannot be re-pointed. Then call `$aliceAgent->setThreadId('demo-alice');` instead and explain why that one is accepted.
2. Add a branch for the argument `shared`: attach `new OfficeHours()` to `$definition` itself, then make a copy with `$definition->for('demo-carol')` and print the tool names of the definition, of Carol's copy and of Bob's copy. Explain why Carol has the tool and Bob does not.
3. Print whether Alice's and Bob's copies share the provider: `json_encode($aliceAgent->getProvider() === $bobAgent->getProvider())`. Say which collaborators a copy shares and why that is safe for a message store keyed by thread.

Acceptance: `repoint` prints a `Refused:` line naming both identifiers; `shared` shows `office_hours` on the definition and on Carol's copy, while Bob's copy, made before the change, still has none; the provider comparison prints `true`.

All of this runs without a key: the script uses a fake provider. The offline check of the lesson, `php verify-copy-not-mutate.php <your project>`, ends with `COPY NOT MUTATE OK`.
