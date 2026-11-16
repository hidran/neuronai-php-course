<?php
declare(strict_types=1);
namespace App\Http\Controllers;
use App\Models\Conversation;
use App\Support\Transcript;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
final class ConversationController
{
    public function index(Request $request): View
    {
        $conversations = Conversation::query()
            ->whereBelongsTo($request->user())
            ->latest('id')
            ->get();
        return view('conversations.index', [
            'conversations' => $conversations,
        ]);
    }
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:80'],
        ]);
        $conversation = Conversation::query()->create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
        ]);
        return redirect()->route('conversations.show', $conversation);
    }
    public function show(
        Conversation $conversation,
        Transcript $transcript,
    ): View {
        Gate::authorize('view', $conversation);
        return view('conversations.show', [
            'conversation' => $conversation,
            'lines' => $transcript->for($conversation),
        ]);
    }
}
