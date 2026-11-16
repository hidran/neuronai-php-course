# Exercise: the last line of each conversation in the list

Start from the Laravel project at the end of the lesson: `DemoSeeder` with Nora and Marco, the login by name and
password, `ConversationController` with `index`, `store` and `show`, the `Transcript` reader, and the Blade views.

The conversation list shows titles only. **Show, under each title, the last line of that conversation**, the way a
chat application does: who said it, and its first sixty characters. The line comes from the stored messages. No
model is called and no agent is touched, exactly as on the conversation page.

## Steps

1. **Seed a conversation that ends with a question.** In `DemoSeeder`, give Nora a second conversation,
   *Return label*. Create it after Marco's, so the first two conversations keep their ids. Append one user message
   to its thread *through the message store*, the same door as the other four:
   `Can I print the <b>return label</b> at home, or do you send it by mail?` The `<b>` tags are part of the
   exercise. Then run `php artisan migrate:fresh --seed`.
2. **Teach the reader one more question.** Add `last(Conversation $conversation): ?TranscriptLine` to
   `Transcript`. It returns the last line of the transcript, or `null` when there is none. *Build it on `for()`*,
   so the rules you already wrote stay in one place: rows without text are skipped, archived rows count.
3. **Pass the previews to the view.** In `ConversationController::index`, receive `Transcript` as a second
   parameter, the way `show` does, and build an array of previews keyed by conversation id. Loop over the
   conversations the query returned: *the query stays scoped to the signed-in user*, so no line of somebody
   else's conversation is ever read.
4. **Print the preview.** In `conversations/index.blade.php`, under the link, add a paragraph with the class
   `preview`. It holds `You:` or `Support:`, depending on the role, then the text cut with
   `Str::limit($preview->text, 60)`, printed with double curly braces. When there is no line, the paragraph says
   `No messages yet.`
5. **Answer in one sentence:** the list now runs one query for each conversation. When would that become a
   problem, and what would you change then?

## Acceptance

Start the server with `php artisan serve` and check in the browser. Both demo users sign in with the password
`support-demo`.

- **Nora's list** shows *Return label* first, with
  `You: Can I print the <b>return label</b> at home, or do you send...` under it. The tags are visible as text
  and nothing is bold. In the page source they arrive as `&lt;b&gt;return label&lt;/b&gt;`.
- Under *Opening hours* the list says `Support: On Saturday we are closed.`
- A conversation started from the form shows `No messages yet.` under its title.
- **Marco's list** shows *Invoice copy* with `No messages yet.`, and no line of Nora's. When Marco types the
  address `/conversations/3`, the answer is 403.
- `routes/web.php`, the `show` action, `TranscriptLine` and the policy are unchanged. The seeder, the reader and
  the controller still name no agent.

To check the whole list without a browser, run `php exercise-last-line-check.php <your project>` from the
lesson's `files` folder. It runs *your* seeders, classes, routes and views on an in-memory database, signs in as
Nora and as Marco through Laravel's HTTP kernel, and ends with `LAST LINE EXERCISE OK`. No model is called. On
the project as the lesson leaves it, it stops at the first missing piece, the seeded *Return label*.

The lesson's offline check, `php verify-support-chat-page.php <your project>`, still ends with
`SUPPORT CHAT PAGE OK` after the exercise. It loads the lesson's own classes and views over the project's, so it
tells you that the project is sound, not that the exercise is done.

## Worked solution

**The seeder.** Nora is created once and kept in a variable, because two conversations need her id. The new
conversation comes after Marco's, and its message goes in through the store, after the four appends that are
already there:

```php
$nora = $this->demoUser('Nora');
$hours = Conversation::query()->create([
    'user_id' => $nora->id,
    'title' => 'Opening hours',
]);
Conversation::query()->create([
    'user_id' => $this->demoUser('Marco')->id,
    'title' => 'Invoice copy',
]);
$label = Conversation::query()->create([
    'user_id' => $nora->id,
    'title' => 'Return label',
]);
```

```php
$store->append($label->threadId(), new UserMessage(
    'Can I print the <b>return label</b> at home, '
    . 'or do you send it by mail?',
));
```

**The reader.** One method in `Transcript`. `array_last()` returns `null` for an empty array, which is exactly
the "no line yet" case:

```php
public function last(Conversation $conversation): ?TranscriptLine
{
    return array_last($this->for($conversation));
}
```

Because it goes through `for()`, a tool call stored at the end of a thread is not the preview: it has no text,
so `for()` already dropped it.

**The controller.** Laravel injects the reader, and the loop only sees the conversations of the signed-in user:

```php
public function index(Request $request, Transcript $transcript): View
{
    $conversations = Conversation::query()
        ->whereBelongsTo($request->user())
        ->latest('id')
        ->get();
    $previews = [];
    foreach ($conversations as $conversation) {
        $previews[$conversation->id] = $transcript->last($conversation);
    }
    return view('conversations.index', [
        'conversations' => $conversations,
        'previews' => $previews,
    ]);
}
```

**The view.** The list item in `conversations/index.blade.php`. The assignment inside `@if` keeps the line in a
variable for the two lines below it:

```blade
<li>
    <a
        href="{{ route('conversations.show', $conversation) }}"
    >{{ $conversation->title }}</a>
    @if ($preview = $previews[$conversation->id])
        <p class="preview">
            {{ $preview->role === 'user' ? 'You' : 'Support' }}:
            {{ Str::limit($preview->text, 60) }}
        </p>
    @else
        <p class="preview">No messages yet.</p>
    @endif
</li>
```

`Str::limit()` cuts the stored text first and the double curly braces escape what is left, so a cut can never
land in the middle of `&lt;`. If you like, one rule in the layout's style block makes the preview look like a
caption: `.preview { margin: 4px 0 0; color: #52606d; }`

**The answer to step 5.** For the handful of conversations one person has, a query for each is fine. For a long
list it is not: then we would read the last row of every thread in a single query, or keep the last line in a
column of `conversations` and update it when a message is stored.
