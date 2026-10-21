# Assignment: Three cities, two units, one honest failure

Source text of the Udemy Assignment placed right after the lecture "Lab: weather and calculator agent" (course 7387949, section "Tools, giving the agent hands"). Each "## Field" heading is one field of the Udemy assignment editor; the text under it is entered as is.

## Title

Three cities, two units, one honest failure

## Description

Extend the weather agent to calculate a three-city mean in Celsius and Fahrenheit, then prove that a missing city and a tool budget stop it from inventing a result.

## Estimated duration (minutes)

25

## Instructions

The lab's examples/30-weather-agent.php accepts Italian city names as separate command line arguments. WeatherTool finds coordinates through Open-Meteo and returns Celsius temperatures. The calculator allowlist contains mean and evaluate. Keep the weather lookup and all calculations in their tools.

1. Run php examples/30-weather-agent.php Turin Milan Rome. Save the three weather values from this run, the calculator inputs, the computed mean, the displayed round numbers and the run counters. Explain whether the model requested the cities in one batch or separate batches.
2. Change the system prompt's calculation step to require mean for the Celsius average, then a separate evaluate call to convert that mean to Fahrenheit using celsius*9/5+32. Keep the rule that forbids partial-data averages, invented values and retries. Update the output instruction to include every city's Celsius value and the mean in both Celsius and Fahrenheit, in at most three sentences.
3. Run the same three-city command again. Recompute both expected results from this run's weather values. The data can change between runs, so compare the calculator with its own run's values. Confirm that mean receives all three values and evaluate receives the returned Celsius mean. The usual counters are three weather runs, one mean run and one evaluate run.
4. Change only WeatherTool's setMaxRuns(4) in the runner to setMaxRuns(2). Run the three-city command and then run echo $?. Record the diagnostic and exit code. Does the calculator's default limit of three allow a third weather call?
5. Restore setMaxRuns(4). Run php examples/30-weather-agent.php Turin Zzxqvplmno, then echo $?. Check that the missing city produces a returned error, no average or Fahrenheit conversion is calculated from partial data, and the agent explains what is missing.
6. For a fixed-input check, call MeanTool::make() with [12.0, 18.0, 24.0], then EvaluateTool::make() with '18*9/5+32' in a small PHP check script using Composer's autoloader. This checks calculator behavior independently of changing weather or the model. Do not put these sample temperatures into the real weather tool.

Do not open or print .env. If the provider cannot answer or the weather service is unavailable, report the actual failure instead of replacing it with sample data.

## Questions

### Question 1

What weather values, calculator inputs and results did your successful three-city run produce? Why can the three weather calls share one round number, and why does that not prove parallel execution?

### Question 2

What happened when the weather limit was two, including the exit code? What exactly does toolMaxRuns(3) limit, and why does it not override setMaxRuns(2)?

### Question 3

How does the missing-city run differ from the exceeded-limit run? Give the calculator's fixed-input answers, and explain why an error tool output is better than a made-up temperature.

## Solutions

### Solution to question 1

The actual weather values vary. Take all three Celsius results from the same trace, add them and divide by three. Multiply that mean by nine, divide by five, and add thirty-two for Fahrenheit. With the fixed inputs 12, 18 and 24, mean returns 18 and evaluate returns 64.4. A successful trace should show each weather value exactly once in the mean inputs and then the mean in the conversion expression. The model can put three tool requests in one ToolCallMessage, so they have one displayed round number. The tool node still executes them sequentially unless parallel execution is separately enabled. The round counter measures requested tool batches, not concurrency or internal HTTP requests. A different model can request cities in separate batches and still be correct.

### Solution to question 2

The third attempted weather call exceeds the weather tool's override and aborts the run. The runner writes Guard tripped: Tool get_current_temperature has been executed too many times - 2 to standard error and exits with code 1. It prints no final completion line and, because trace printing happens after chat returns, no completed trace. The framework's counter is incremented before the check, so a refused attempt counts as an attempt. The weather tool's setMaxRuns(2) takes precedence over the agent's fallback of three. toolMaxRuns(3) sets a separate default allowance for each other tool by its run key. It is not a shared three-call budget for the agent, nor a count of model responses or HTTP requests.

### Solution to question 3

With the weather budget restored, the missing city produces an Error line that says no city was found and asks the agent not to retry. A compliant model explains why it cannot compute the requested average. It does not average the remaining city or convert an invented mean. The runner reaches Weather lab complete. and exits 0 because the conversation completed with an explanation. By contrast, the exceeded limit aborts chat and exits 1. The fixed calculator inputs return 18 Celsius and 64.4 Fahrenheit. An explicit missing-data result preserves what we actually know and lets the model give a useful explanation. A guessed temperature would turn missing evidence into an apparently precise but unsupported average. The prompt is behavioral guidance, so we still inspect the trace rather than assume compliance.

### Example solution (instructor)

Replace the calculation instruction in the runner with these lines. Keep the other prompt steps, including the missing-data rule:

```php
        'Use mean for the average. Then use evaluate to convert'
            . ' that mean to Fahrenheit with celsius*9/5+32.',
```

Replace the output instruction with:

```php
    output: ['State each city in Celsius, then the mean in'
        . ' Celsius and Fahrenheit. At most three sentences.'],
```

The deliberate limit change is:

```php
$weather = WeatherTool::make($http)->setMaxRuns(2);
```

Restore four after observing the failure. No additional tools or dependencies are required. Both required calculator tools are already in the allowlist.

Fixed-input check, run from the project root as a PHP file:

```php
<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
use NeuronAI\Tools\Toolkits\Calculator\EvaluateTool;
use NeuronAI\Tools\Toolkits\Calculator\MeanTool;
echo MeanTool::make()([12.0, 18.0, 24.0]) . PHP_EOL;
echo EvaluateTool::make()('18*9/5+32') . PHP_EOL;
```

Expected output:

```text
18
64.4
```

In the verified live run, Turin returned 14.3 Celsius, Milan 16.3, and Rome 22.2. The mean was 17.6 Celsius and the evaluate result was 63.68 Fahrenheit. The weather limit of two aborted with exit code 1; the restored limit and missing city produced an explanation with no calculator call and exit code 0. These values are examples, not constants students should reproduce.
