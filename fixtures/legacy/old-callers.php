<?php
// Older tutorial snippets, kept as a fixture: never run them.
declare(strict_types=1);
use NeuronAI\Agent;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\SystemPrompt;
$prompt = new SystemPrompt(background: ['Answer short PHP questions.']);
$agent = Agent::make()->withInstructions((string) $prompt);
// The return of chat() is treated as the final text.
$response = $agent->chat(new UserMessage('What is a trait?'));
echo $response->getContent() . PHP_EOL;
// events() is called on the result of stream().
$stream = $agent->stream(new UserMessage('And an enum?'));
foreach ($stream->events() as $chunk) {
    echo $chunk;
}
