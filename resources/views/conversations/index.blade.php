@extends('layouts.app')
@section('title', 'Your conversations')
@section('content')
    <h1>Your conversations</h1>
    <ul class="list">
        @forelse ($conversations as $conversation)
            <li>
                <a
                    href="{{ route('conversations.show', $conversation) }}"
                >{{ $conversation->title }}</a>
            </li>
        @empty
            <li class="empty">No conversations yet.</li>
        @endforelse
    </ul>
    <form method="post" action="{{ route('conversations.store') }}"
        class="card">
        @csrf
        <label for="title">New conversation</label>
        <input id="title" name="title" value="{{ old('title') }}"
            maxlength="80" autocomplete="off" required>
        @error('title')
            <p class="error">{{ $message }}</p>
        @enderror
        <button type="submit">Start</button>
    </form>
@endsection
