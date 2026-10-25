# Exercise: count the tool calls and hit the cap

Use the project as this lesson leaves it: `src/PackageVersionTool.php`, the `tools()` hook of `src/HelloAgent.php` and `examples/02-first-tool.php`. Every step runs the lesson's script in the PhpStorm Terminal and needs a provider key in `.env`.

1. Ask about two packages in one question and count the `Tool result` lines and the steps:

   ```text
   php examples/02-first-tool.php "Which versions of neuron-core/neuron-ai and vlucas/phpdotenv are installed?"
   ```

   Expect two `Tool result` lines. With most providers there are still four steps, because the model puts both calls in one `ToolCallMessage`; two runs of the same tool stay under the cap of three.
2. Ask about a package that is not installed, such as `laravel/framework`, and read how the model uses the "is not installed" sentence your tool returned.
3. Lower the cap to `toolMaxRuns(1)` in the script and ask about three packages. Read the `ToolRunsExceededException` that stops the run: the second call of the same tool is one too many. Put the cap back to three afterwards.

If an answer comes back in another language, add "Always answer in English." to the output section of the agent's instructions and run again.
