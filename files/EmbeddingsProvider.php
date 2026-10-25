<?php
declare(strict_types=1);
namespace App\Config;
enum EmbeddingsProvider: string
{
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
