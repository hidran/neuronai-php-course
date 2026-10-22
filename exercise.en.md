# Exercise: distinguish a returned object from a lookup

1. Extract from "My name is Mia Chen. My favorite food is sushi." using PersonExtractor's default Person contract.
2. Print the returned type and verify that the unstated age remains null.
3. On a separate HelloAgent and thread, request the installed vlucas/phpdotenv version through get_package_version.
4. Verify that the lookup actually ran, using the final state's getToolRuns('get_package_version'). Print the final reply.
5. Explain which schema describes arguments, which describes the final answer, and whether either operation saves a person.

## Worked solution

Copy files/exercise-output-vs-tools.php to examples/exercise-output-vs-tools.php in the completed learner project. Run it
from PhpStorm Terminal with `php examples/exercise-output-vs-tools.php`.

The solution throws if the age is invented, the name differs, or the tool execution count is below one. It prints:

```text
Data type: App\Output\Person
Age: not stated
Tool called: get_package_version with vlucas/phpdotenv
Chat type: NeuronAI\Agent\AgentState
Lookup runs: 1
[model answer with the installed package version]
Exercise complete.
```

The run count can exceed one if the agent repeats its lookup. Wording and installed versions can vary. The solution does
not assert those variable strings. Person describes a final DTO; the tool's package property describes a function argument.
The function reads Composer metadata. Neither the DTO nor this lookup persists a person. Tools that do write require
application decisions about authorization and repeat execution. Structured output can still use tools before returning.
