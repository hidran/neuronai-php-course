# Exercise: compare two observed runs

Run the baseline and the modified version of the trace script, each once:

```
php examples/50-reading-traces.php baseline
php examples/50-reading-traces.php modified
```

The modified run uses the same question, tools and input schemas; only the weather tool description is weaker. Both reports are saved in `storage/trace-baseline.json` and `storage/trace-modified.json`, and `php examples/50-reading-traces.php baseline show` prints a saved report again without calling the model.

For each execution, answer four questions using its actual evidence: how many inference requests happened, which tools ran with which inputs and results, how inference and tool durations compare with wall time, and how reported input and output usage (and the estimated cost) changed across requests. Use call identifiers to connect repeated uses of the same tool. Record tool errors independently from final-answer accuracy.

Compare the offered descriptions and input schemas in the two JSON files. The weather input must still require a city string, and mean must still require an array of numbers. If both runs are correct, write that result. Do not manufacture a failure or conclude equal reliability from two executions. Propose one targeted hypothesis supported by the observations and name the additional representative cases that would test it.

Replace the placeholder prices in the script with the current prices of your provider and model before trusting the cost line. A total of zero means the configured model is missing from the price map.

Acceptance: run `php examples/exercise-reading-traces.php`. It checks complete evidence in both reports, unchanged input schemas, a changed description and unique labels, and prints the inferences, tools and estimated cost of each run. This exercise uses real provider calls; it is not an offline deterministic test.

Optional: with an Inspector ingestion key configured, add `inspector` as the second argument and match each dashboard transaction to its run label.
