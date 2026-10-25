# Exercise: separate schema hints from runtime limits

Use the completed property types project: the check packages tool and the script with its dry run. Steps 1 to 3 are local and need no provider; step 4 makes a few model calls.

1. Feed the tool the way the agent does: build a fresh tool, call `setInputs(['requirements' => ...])`, then `execute()`, and print `getResult()`. Try three inputs: an item whose `minimum` is the number `5.6`, an item with a `minimum` but no `package`, and the plain string `"vlucas/phpdotenv"` instead of the list. Explain each result.
2. Call the tool directly, `$tool([...])`, with the item that has no `package`. Which checks did the direct call skip, and what did our defensive reads still cover?
3. Run the dry run and find `maxItems: 5` in the schema. Then pass eight valid requirements through `setInputs()` and `execute()` and count the report lines. Add a guard at the top of `__invoke()`, before `$lines`, that returns `ToolOutput::error('At most 5 packages per call.')` when `count($requirements)` is above five, and run the eight items again.
4. Ask the agent about eight packages in one question, before and after the guard, with `php examples/22-property-types.php "..."`. Read every `Model sent:` line and the step count. The model may split the list, retry in smaller batches after the error, or explain the limit: record what your model actually did.

Copy `files/exercise-package-inputs.php` into `examples/` and run in the PhpStorm Terminal:

```text
php examples/exercise-package-inputs.php
```

Expected lines before the guard (the installed phpdotenv version may differ):

```text
Numeric minimum: ok | vlucas/phpdotenv: v5.7.0, meets 5.6
Missing package: error | Parameter "requirements" element 0 field "package" is required.
Plain string: error | Parameter "requirements" must be of type array, string given.
Direct call, missing package: : not installed
Eight requirements: 8 report lines
Package input exercise complete.
```

After the guard, the eighth-item line becomes:

```text
Eight requirements: rejected | At most 5 packages per call.
```

What to take away: NeuronAI casts the number to the string our property asks for, and it stops a missing required field or a wrong type before `__invoke()` runs, with an error the model can read. A direct call skips all of that, and the defaults turn the broken item into a "not installed" line. `maxItems` travels in the schema as a hint, but binding does not count the elements: the guard is what guarantees at most five packages per call, on the agent path and on a direct call alike. It does not guarantee how the model reacts, which is why step 4 looks at the real steps.
