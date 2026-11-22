@extends('layouts.app')
@section('title', $conversation->title)
@section('content')
    <h1>{{ $conversation->title }}</h1>
    <ul id="transcript" class="transcript">
        @forelse ($lines as $line)
            <li class="{{ $line->role }}">{{ $line->text }}</li>
        @empty
            <li class="empty">No messages yet.</li>
        @endforelse
    </ul>
    <form id="message-form" method="post" class="card">
        @csrf
        <fieldset disabled>
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="2"
                maxlength="2000" required></textarea>
            <button type="submit">Send</button>
        </fieldset>
    </form>
    <p id="form-error" class="error"></p>
@endsection
