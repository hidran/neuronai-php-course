<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/../../09-01-mcp-local-server/files/McpConnection.php';
// Part one typed McpPolicy and owns the fixture: they stay in its files/.
$partOne = __DIR__ . '/../../09-03-mcp-filtering-security/files';
require __DIR__ . '/FramedMcpTool.php';
require $partOne . '/McpPolicy.php';
require __DIR__ . '/PolicyAgent.php';
use App\Agents\PolicyAgent;
use App\Mcp\McpPolicy;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\ToolCallMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Testing\FakeAIProvider;
use NeuronAI\Tools\ToolCall;
use NeuronAI\Workflow\WorkflowStatus;
// The stdio fixture needs the lab autoloader for App\Mcp\PolicyFixture.
@mkdir($lab . '/servers');
@mkdir($lab . '/src/Mcp', recursive: true);
@mkdir($lab . '/storage');
copy($partOne . '/policy-tools.php', $lab . '/servers/policy-tools.php');
copy($partOne . '/PolicyFixture.php', $lab . '/src/Mcp/PolicyFixture.php');
$audit = $lab . '/storage/mcp-policy.jsonl';
file_put_contents($audit, '');
try {
    $policy = new McpPolicy($lab . '/servers/policy-tools.php');
    if ($policy->select([]) !== [] || $policy->connections() !== 0) {
        throw new RuntimeException('deny-all built a connection');
    }
    $reads = $policy->select(McpPolicy::READS);
    if (McpPolicy::names($reads) !== ['get_office_hours', 'list_locations']) {
        throw new RuntimeException('select failed');
    }
    $framed = $policy->framed(['get_office_hours']);
    $text = (string) $framed[0](location: 'north');
    if (!str_contains($text, '<tool_result name="get_office_hours"')
        || !str_contains($text, '09:00-17:00')) {
        throw new RuntimeException("framing: {$text}");
    }
    $preview = $policy->approvalPreview();
    if ($preview[0]->requiresApproval() !== true) {
        throw new RuntimeException('approval flag missing');
    }
    $call = new ToolCall('maintenance_preview', 'm1', []);
    $fake = new FakeAIProvider(
        new ToolCallMessage(null, [$call]),
        new AssistantMessage('The server denied the maintenance preview.'),
    );
    $agent = PolicyAgent::make($fake, $policy, true)->setThreadId('t');
    if (count($agent->getTools()) !== 3) {
        throw new RuntimeException('expected three tools');
    }
    $state = $agent->chat(new UserMessage('Run the maintenance preview.'));
    if ($state->getStatus() !== WorkflowStatus::Suspended) {
        throw new RuntimeException('run did not pause for approval');
    }
    $pending = $agent->pendingApprovals();
    if (count($pending) !== 1 || $pending[0]->name !== 'maintenance_preview') {
        throw new RuntimeException('pending approvals wrong');
    }
    $state = $agent->submitApprovalDecisions([$pending[0]->id => 'approve'])
        ->run();
    if ($state->getStatus() !== WorkflowStatus::Completed
        || $state->getToolRuns('maintenance_preview') !== 1
        || $fake->getCallCount() !== 2) {
        throw new RuntimeException('resume failed');
    }
    $entries = file($audit, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $last = json_decode(end($entries), true);
    if ($last['name'] !== 'maintenance_preview' || $last['outcome'] !== 'denied') {
        throw new RuntimeException('audit: ' . json_encode($entries));
    }
} finally {
    unlink($lab . '/servers/policy-tools.php');
    unlink($lab . '/src/Mcp/PolicyFixture.php');
}
echo 'MCP POLICY OK' . PHP_EOL;
