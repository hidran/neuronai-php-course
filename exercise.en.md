# Exercise: two drivers, one manager

Start from the Laravel project at the end of the lesson: `php artisan components provider` compares the default driver resolved twice, and `VectorStoreManager` is a singleton in `AppServiceProvider`.

1. Add a check `named` to `Components.php`: resolve `AIProvider::driver('openai')` twice and `AIProvider::driver()` once, and print whether the two named calls return the same object and whether the named one is the default one. Run it with `NEURON_AI_PROVIDER=anthropic` and explain both answers. `OPENAI_KEY` must be defined, even empty: the SDK passes it to a `string` parameter, and a missing variable is a `TypeError`.
2. In `AppServiceProvider::boot()`, register the memory vector driver once for the whole application with `VectorStore::extend('memory', fn () => new MemoryVectorStore())`, and remove the `extend()` call from the command. Run `php artisan components vector`: both paths must still print `MemoryVectorStore`.
3. Comment out the singleton line and run the vector check again. Explain why the container's manager now misses a driver registered in `boot()`, then restore the line.
4. Give `SupportDocsAgent` its own provider object with a contextual binding in `AppServiceProvider::register()`: `$this->app->when(SupportDocsAgent::class)->needs(AIProviderInterface::class)->give(...)`, building an `Anthropic` provider from `config('neuron.provider.anthropic')`. Resolve the agent with `app(SupportDocsAgent::class)` and print whether its `getProvider()` is the same object as `AIProvider::driver()`.

Acceptance: the two `openai` calls share one object, which is not the default `anthropic` object; with the singleton the vector check prints `MemoryVectorStore` twice; without it the container path reports `Driver [memory] not supported.`; with the contextual binding the agent's provider is not the shared default driver.

No key is needed for steps 2 and 3, and building a provider object in steps 1 and 4 makes no network call. The lesson's offline check, `php verify-component-facades.php <your project>`, ends with `COMPONENT FACADES OK`.
