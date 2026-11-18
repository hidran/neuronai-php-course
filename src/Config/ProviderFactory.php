<?php
declare(strict_types=1);
namespace App\Config;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
use NeuronAI\Providers\Gemini\Gemini;
use NeuronAI\Providers\Ollama\Ollama;
use NeuronAI\Providers\OpenAI\OpenAI;
final readonly class ProviderFactory
{
    public function __construct(private Settings $settings)
    {
    }
    #[\NoDiscard]
    public function create(array $parameters = []): AIProviderInterface
    {
        $s = $this->settings;
        return match ($s->provider) {
            Provider::Anthropic => new Anthropic(
                $s->key, $s->model, parameters: $parameters,
            ),
            Provider::OpenAI => new OpenAI(
                $s->key, $s->model, parameters: $parameters,
            ),
            Provider::Gemini => new Gemini(
                $s->key, $s->model, parameters: $parameters,
            ),
            Provider::Ollama => new Ollama(
                $s->url ?? 'http://localhost:11434/api',
                $s->model, parameters: $parameters,
            ),
        };
    }
}
