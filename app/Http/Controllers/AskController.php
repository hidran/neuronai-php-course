<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Neuron\Agents\AssistantAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\UniqueIdGenerator;
final class AskController
{
    public function __invoke(
        Request $request,
        AssistantAgent $agent,
    ): JsonResponse {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $state = $agent
            ->for(UniqueIdGenerator::generateId('ask_'))
            ->chat(new UserMessage($validated['message']));
        $answer = $state->getMessage()?->getContent();
        if ($answer === null || $answer === '') {
            return response()->json(
                ['error' => 'The agent returned no answer.'],
                502,
            );
        }
        return response()->json(['answer' => $answer]);
    }
}
