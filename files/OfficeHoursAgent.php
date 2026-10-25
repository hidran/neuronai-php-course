<?php
declare(strict_types=1);
namespace App\Agents;
use App\PackageVersionTool;
use NeuronAI\Agent\Agent;
use NeuronAI\Agent\SystemPrompt;
use NeuronAI\Chat\Messages\SystemMessage;
use NeuronAI\MCP\McpConnector;
use NeuronAI\Providers\AIProviderInterface;
final class OfficeHoursAgent extends Agent
{
    public function __construct(
        AIProviderInterface $provider,
        McpConnector $connector,
    ) {
        parent::__construct();
        $this->setAiProvider($provider);
        $this->addTool([new PackageVersionTool(),
            ...$connector->tools()]);
    }
    #[\Override]
    protected function instructions(): SystemMessage
    {
        return new SystemMessage((string) new SystemPrompt(
            background: ['You answer questions about our offices.'],
            output: [
                'Report the exact hours and timezone from the tool.',
                'Answer in one short plain text sentence.',
            ],
            toolsUsage: [
                'Use get_office_hours for every office hours question.',
                'Never guess hours.',
            ],
        ));
    }
}
