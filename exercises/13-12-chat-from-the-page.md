# Exercise: a limit on messages from the page

Start from the Laravel project at the end of the lesson: the web message route `conversations.messages.web` on
`ConversationMessageController`, the enabled form, and the page script with `line()`, `send()`, `failure()` and
the submit handler.

Every message that passes validation costs a model call, and nothing stops a signed-in user, or a script that
holds their session cookie, from sending hundreds. **Limit the web route to three messages a minute for each
signed-in user, and make the page say so in a sentence a person can read.** The controller does not change, and
neither does the API route.

Three a minute is low on purpose: it makes the limit easy to reach by hand. Laravel documents the two pieces
you need under *Rate Limiting*: `https://laravel.com/docs/13.x/routing#rate-limiting`

## Steps

1. **Define the limiter.** In `App\Providers\AppServiceProvider`, add a `boot()` method that registers a rate
   limiter named `chat`: `RateLimiter::for('chat', ...)` with a closure that receives the request and returns
   `Limit::perMinute(3)->by(...)`. *Count by the id of the request's user.* Like the owner of a new conversation,
   the key comes from the session, never from anything the browser sends.
2. **Attach it to the web route only.** In `routes/web.php`, add `->middleware('throttle:chat')` to the message
   route. Then ask Artisan what we have now, as in the lesson: `php artisan route:list --path=messages -v`
3. **Give the status a sentence.** A request over the limit is answered with status 429 before it reaches the
   controller. In `failure()`, return `Too many messages. Wait a minute.` for it.
4. **Decide what the handler does, before you touch it.** Was the question stored? Should the box keep its
   text? Compare with the two cases the handler already knows, the 502 and every other error, and *change the
   handler only if neither fits*.
5. **Try it without spending a model call.** Sign in as Marco, open *Invoice copy*, type three spaces and press
   Send four times. The box keeps the spaces after each refusal, so pressing Send again is enough. Then answer
   in one sentence: why do messages that fail validation use up the limit?
6. **Check who is counted.** Within the same minute, sign out, sign in as Nora and send three spaces in
   *Opening hours*.
7. **Choose the real number.** When everything works, replace three with a limit you would defend for a real
   support chat, for example twenty a minute.

If every Send now answers `Something went wrong, try again.`, the name after `throttle:` is not the name you
gave to `RateLimiter::for`.

## Acceptance

Check with the limit at three. The counters live in the cache: a minute of waiting ends the limit, and
`php artisan cache:clear` ends it at once.

- `php artisan route:list --path=messages -v` shows `Illuminate\Routing\Middleware\ThrottleRequests:chat` under
  the web route, after `web` and `Illuminate\Auth\Middleware\Authenticate`. The API route still shows `api` and
  `Illuminate\Auth\Middleware\Authenticate:sanctum` only.
- **Marco's first three Sends** show Laravel's `The message field is required.` under the form. **The fourth**
  shows `Too many messages. Wait a minute.` All four lines are labeled "You, not sent", the box keeps its text
  and the Send button is blue again.
- A real question sent in that same minute gets the same sentence. After a reload the transcript has no new
  line: no model was called and nothing was stored.
- **Nora**, in the same minute, gets `The message field is required.` for her spaces, not the limit.
- A minute later Marco's messages go through again.
- `ConversationMessageController.php` and `routes/api.php` are unchanged.

To check the list without a browser and without waiting a minute, run
`php exercise-message-limit-check.php <your project>` from the lesson's `files` folder. It runs *your* provider,
routes and view on an in-memory database and an in-memory cache, posts JSON the way the page script does,
moves the clock forward by a minute for the last step, and ends with `MESSAGE LIMIT EXERCISE OK`. It reads the
limit from your limiter, so it passes at three and at the number you choose in step 7. No model is called. On
the project as the lesson leaves it, it stops at the first missing piece, the `throttle:chat` middleware.

`php artisan test` still passes after the exercise: it tells you that the project is sound, not that the exercise
is done.

## Worked solution

**The limiter.** Three imports and a `boot()` method in `AppServiceProvider`, next to `register()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
```

```php
public function boot(): void
{
    RateLimiter::for('chat', fn (Request $request) =>
        Limit::perMinute(3)->by($request->user()->id));
}
```

The route sits in the `auth` group, and Laravel runs the session guard before the limiter. So there is always a
user here: a guest gets the 401 and is never counted. The count belongs to the user, not to a conversation, so
opening a second conversation does not buy three more messages.

**The route.** One call added to the route the lesson wrote:

```php
Route::post(
    '/conversations/{conversation}/messages',
    ConversationMessageController::class,
)->middleware('throttle:chat')->name('conversations.messages.web');
```

The API route is a different route with its own middleware, so it stays as it was. That is the other side of
"one action, two guards": what protects one door is decided on that door.

**The sentence.** One line in `failure()`, after the line for 419:

```js
if (status === 429) return 'Too many messages. Wait a minute.';
```

**The handler does not change.** The limiter answers before the controller runs, so the agent never saw the
question and nothing was stored. That is the "every other error" case the handler already has: the question is
marked not sent and the box keeps its text, ready to be sent again when the minute is over. Only the 502 is
different, because there the question is already in the table.

**The answer to step 5.** The limiter runs before the controller, so it counts requests, not answers. The
message of three spaces is refused by validation inside the controller, after it was counted. The same is true
for a request the policy refuses. That is what we want from a limit: a script that sends garbage is stopped as
early as one that sends real questions.

**Step 6.** Nora's spaces get the validation sentence because the count is kept for each user id. Marco being
over his limit says nothing about her.
