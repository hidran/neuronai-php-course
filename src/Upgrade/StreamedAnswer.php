<?php
declare(strict_types=1);
namespace App\Upgrade;
use NeuronAI\Agent\AgentState;
final readonly class StreamedAnswer
{
    public function __construct(
        public string $text,
        public int $textChunks,
        public AgentState $state,
    ) {
    }
}
