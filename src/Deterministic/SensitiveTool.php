<?php
declare(strict_types=1);
namespace App\Deterministic;
use NeuronAI\Tools\Tool;
final class SensitiveTool extends Tool
{
    protected string $name = 'sensitive_teaching_tool';
    protected ?string $description = 'Reads a protected value.';
    public function __construct(
        private readonly SensitiveService $service,
        private readonly bool $authorized,
    ) {
        $this->visible($authorized);
    }
    public function __invoke(): string
    {
        return $this->service->read($this->authorized);
    }
}
