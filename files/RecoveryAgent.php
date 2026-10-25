<?php
declare(strict_types=1);
namespace App\Deterministic;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
final class RecoveryAgent extends Agent
{
    public function __construct(
        AIProviderInterface $provider,
        private readonly ToolErrorPolicy $policy
            = new ToolErrorPolicy(),
    ) {
        parent::__construct();
        $this->setAiProvider($provider);
    }
    #[\Override]
    protected function resolveToolErrorHandler(): ?callable
    {
        return $this->policy;
    }
}
