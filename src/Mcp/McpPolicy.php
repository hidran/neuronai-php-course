<?php
declare(strict_types=1);
namespace App\Mcp;
use NeuronAI\MCP\McpTool;
use NeuronAI\Tools\ToolInterface;
use RuntimeException;
final class McpPolicy
{
    public const array READS = ['get_office_hours', 'list_locations'];
    private int $connections = 0;
    public function __construct(private readonly string $server)
    {
    }
    /** @param list<string> $flags */
    public function connection(array $flags = []): McpConnection
    {
        $this->connections++;
        return McpConnection::local($this->server, $flags);
    }
    public function connections(): int
    {
        return $this->connections;
    }
    /**
     * @param list<string> $allowed
     * @param list<string> $flags
     * @return list<ToolInterface>
     */
    public function select(array $allowed, array $flags = []): array
    {
        if ($allowed === []) {
            return [];
        }
        $tools = $this->connection($flags)->only($allowed)->tools();
        self::assertExact($tools, $allowed);
        return $tools;
    }
    /**
     * Reads whose results reach the model framed as data.
     *
     * @param list<string> $allowed
     * @return list<ToolInterface>
     */
    public function framed(array $allowed): array
    {
        if ($allowed === []) {
            return [];
        }
        $connection = $this->connection()->only($allowed);
        foreach ($allowed as $name) {
            $connection = $connection->with($name,
                FramedMcpTool::wrap(...));
        }
        $tools = $connection->tools();
        self::assertExact($tools, $allowed);
        return $tools;
    }
    /** @return list<ToolInterface> */
    public function approvalPreview(): array
    {
        return $this->connection()->only(['maintenance_preview'])
            ->with('maintenance_preview',
                fn (McpTool $tool) => $tool->requireApproval())
            ->tools();
    }
    /**
     * @param list<ToolInterface> $tools
     * @return list<string>
     */
    public static function names(array $tools): array
    {
        $names = array_map(
            static fn (ToolInterface $tool): string => $tool->getName(),
            $tools,
        );
        sort($names);
        return $names;
    }
    /**
     * @param list<ToolInterface> $tools
     * @param list<string> $allowed
     */
    public static function assertExact(array $tools, array $allowed)
        : void
    {
        sort($allowed);
        if (self::names($tools) !== $allowed
            || count(array_unique($allowed)) !== count($allowed)) {
            throw new RuntimeException('Required MCP capability missing');
        }
    }
}
