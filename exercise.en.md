# Exercise: a default contract and a missing fact

Use the completed PersonExtractor, including its getOutputClass() method.

1. Extract a Person from: "My name is Mia Chen. My favorite food is sushi."
2. Request the default shape, without passing Person::class to structured().
3. Print the result's class, name, preference and age. Show "not stated" when age is null. Do not infer an age.
4. On a fresh agent with another thread ID, send the same message through chat(). Print the returned value's class.
5. Explain why declaring getOutputClass() does not make those two return types identical.

Expected checks: structured() returns App\Output\Person, the name is Mia Chen, the food is sushi, and age remains null.
chat() returns NeuronAI\Agent\AgentState. Its natural language content may vary and is not part of the check.

## Worked solution

The complete runnable solution is `files/exercise-request-structured.php`. Copy it to `examples/exercise-request-structured.php`
in the prepared project after applying the lesson's default agent version, then run:

```text
php examples/exercise-request-structured.php
```

The solution calls structured() with only the message. PersonExtractor supplies Person::class through getOutputClass().
The nullable age and its null default let an unstated age remain absent without an uninitialized property error.
A fresh agent and thread isolate the chat comparison from extraction history.

```text
Structured type: App\Output\Person
Name: Mia Chen
Food: sushi
Age: not stated
Chat type: NeuronAI\Agent\AgentState
Exercise complete.
```

getOutputClass() supplies a default to structured(). The chat() entry point still returns the final AgentState.
We access the DTO properties directly after structured(); for a chat reply, the text is inside the state's message.
The runtime check rejects an invented age, but does not claim that all extraction errors are prevented.
