<?php
declare(strict_types=1);
namespace App\Agents;
use NeuronAI\Agent\Agent;
use NeuronAI\Providers\AIProviderInterface;
use NeuronAI\Providers\Anthropic\Anthropic;
class CouponExtractor extends Agent
{
    protected function provider(): AIProviderInterface
    {
        return new Anthropic(
            key: $_ENV['ANTHROPIC_KEY'],
            model: $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-5-5',
        );
    }
    protected function instructions(): string
    {
        return 'Extract the coupon code exactly as stated. '
            . 'Summarize the offer in one to six words. '
            . 'Never invent a different coupon code.';
    }
}
