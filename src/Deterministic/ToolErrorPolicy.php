<?php
declare(strict_types=1);
namespace App\Deterministic;
use NeuronAI\Tools\ToolCall;
use RuntimeException;
use Throwable;
final readonly class ToolErrorPolicy
{
    /** A recovery instruction for the model, or null to rethrow. */
    public function __invoke(Throwable $error, ToolCall $call): ?string
    {
        if (!$error instanceof RuntimeException) {
            return null;
        }
        return 'Weather unavailable. Retry with valid coordinates.';
    }
}
