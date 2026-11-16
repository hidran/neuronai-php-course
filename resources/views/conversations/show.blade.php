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
    <form id="message-form" method="post" class="card"
        action="{{ route('conversations.messages.web', $conversation) }}">
        @csrf
        <fieldset>
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="2"
                placeholder="Type your question"
                maxlength="2000" required></textarea>
            <button type="submit">Send</button>
        </fieldset>
    </form>
    <p id="form-error" class="error"></p>
    <script>
        const form = document.getElementById('message-form');
        const input = form.querySelector('textarea');
        const button = form.querySelector('button');
        const list = document.getElementById('transcript');
        const error = document.getElementById('form-error');
        const token = document.querySelector('meta[name="csrf-token"]');
        function line(role, text) {
            const item = document.createElement('li');
            item.className = role;
            item.textContent = text;
            list.append(item);
            window.scrollTo(0, document.body.scrollHeight);
            return item;
        }
        async function send(message) {
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token.content,
                    },
                    body: JSON.stringify({ message }),
                });
                const body = await response.json();
                return { status: response.status, body };
            } catch {
                return { status: 0, body: {} };
            }
        }
        function failure(status, body) {
            const first = body.errors?.message?.[0];
            if (status === 422 && first) return first;
            if (status === 502 && body.error) return body.error;
            if (status === 403) return 'This conversation is not yours.';
            if (status === 419) return 'This page expired. Reload it.';
            return 'Something went wrong, try again.';
        }
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            error.textContent = '';
            list.querySelector('.empty')?.remove();
            const question = line('user', input.value);
            const thinking = line('assistant thinking', 'Thinking...');
            button.disabled = true;
            const { status, body } = await send(input.value);
            button.disabled = false;
            thinking.remove();
            if (status === 401) {
                window.location.reload();
            } else if (status === 200) {
                line('assistant', body.answer);
                input.value = '';
            } else {
                error.textContent = failure(status, body);
                if (status === 502) input.value = '';
                else question.classList.add('not-sent');
            }
            input.focus();
        });
    </script>
@endsection
