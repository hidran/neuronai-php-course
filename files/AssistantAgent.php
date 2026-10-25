<?php
declare(strict_types=1);
namespace App\Agents;
use Composer\InstalledVersions;
use DateTimeImmutable;
use DateTimeZone;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\ContentBlocks\TextContent;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\Providers\AIProviderInterface;
final class AssistantAgent extends Agent
{
    public function __construct(AIProviderInterface $provider)
    {
        parent::__construct();
        $this->setAiProvider($provider);
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return new SystemMessage((string) new SystemPrompt(
            background: [
                'You are a friendly assistant for PHP developers.',
                'You talk to developers who know the basics.',
            ],
            steps: [
                'Find the PHP feature behind the question.',
                'Prefer the modern way, from PHP 8 on.',
                'Write the shortest example that works.',
            ],
            output: [
                'Plain text only.',
                'Three lines, labeled Topic, Summary and Example.',
                'The summary is one short sentence.',
                'The example is one line of PHP.',
            ],
        ));
    }
    #[\Override]
    protected function context(): array
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $version = InstalledVersions::getPrettyVersion(
            'neuron-core/neuron-ai',
        );
        return [
            new TextContent('Now: ' . $now->format('Y-m-d H:i T')),
            new TextContent("Installed NeuronAI: {$version}"),
        ];
    }
}
