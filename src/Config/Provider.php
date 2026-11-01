<?php
declare(strict_types=1);
namespace App\Config;
enum Provider: string
{
    case Anthropic = 'anthropic';
    case OpenAI = 'openai';
    case Gemini = 'gemini';
    case Ollama = 'ollama';
    public function needsKey(): bool
    {
        return $this !== self::Ollama;
    }
    public function prefix(): string
    {
        return strtoupper($this->value);
    }
}
