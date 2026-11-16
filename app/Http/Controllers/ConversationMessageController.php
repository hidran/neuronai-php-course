<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Models\Conversation;
use App\Neuron\Agents\SupportAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use NeuronAI\Chat\Messages\UserMessage;
final class ConversationMessageController
{
    public function __invoke(
        Request $request,
        Conversation $conversation,
        SupportAgent $agent,
    ): JsonResponse {
        Gate::authorize('view', $conversation);
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);
        $state = $agent
            ->for($conversation->threadId())
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
