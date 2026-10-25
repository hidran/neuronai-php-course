# Exercise: a returned object, a lookup, and both together

1. Extract from "My name is Mia Chen. My favorite food is sushi." using PersonExtractor's default Person contract.
   Verify that the name is exactly Mia Chen and that the unstated age stays null. Print the returned type.
2. On a separate HelloAgent and thread, request the installed vlucas/phpdotenv version through get_package_version.
   Verify that the lookup actually ran, using the final state's getToolRuns('get_package_version'). Print the reply.
3. On a plain Agent with PackageVersionTool attached, call structured() with PackageReport as the output class.
   Verify that the reported version is the one Composer has installed, not one the model remembered.
4. Explain which schema describes arguments, which describes the final answer, and whether any step saves something.

## Worked solution

Copy files/exercise-output-vs-tools.php to examples/exercise-output-vs-tools.php in the completed learner project (with
src/Output/PackageReport.php from the lesson). Run it from PhpStorm Terminal with
`php examples/exercise-output-vs-tools.php`.

The solution throws if the age is invented, the name differs, the tool ran zero times, or the report's version does not
contain `Composer\InstalledVersions::getPrettyVersion('vlucas/phpdotenv')` (leading v trimmed). It prints:

```text
Data type: App\Output\Person
Age: not stated
Chat type: NeuronAI\Agent\AgentState
Lookup runs: 1
[model answer with the installed package version]
Report type: App\Output\PackageReport
Reported version: [the installed version]
Exercise complete.
```

The run count can exceed one if the agent repeats its lookup. Wording and installed versions vary; the solution does
not assert the free text. Person and PackageReport describe final DTOs; the tool's package property describes a
function argument. The function reads Composer metadata. Nothing here persists a person or a report. Tools that do
write require application decisions about authorization and repeat execution.
