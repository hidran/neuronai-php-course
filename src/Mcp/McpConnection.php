<?php
declare(strict_types=1);
namespace App\Mcp;
use Closure;
use NeuronAI\MCP\McpConnector;
use NeuronAI\MCP\McpTool;
final class McpConnection
{
    private ?McpConnector $connector = null;
    /**
     * @param array<string, mixed> $config
     * @param list<string> $only
     * @param array<string, Closure> $with name => callback(tool, connector)
     */
    public function __construct(
        private readonly array $config,
        private readonly array $only = [],
        private readonly array $with = [],
    ) {
    }
    /** @param list<string> $args */
    public static function local(string $script, array $args = []): self
    {
        return new self([
            'command' => PHP_BINARY,
            'args' => [$script, ...$args],
            'timeout' => 5,
        ]);
    }
    public static function http(string $url, int $timeout = 3): self
    {
        return new self(['url' => $url, 'timeout' => $timeout]);
    }
    /** @param list<string> $names */
    public function only(array $names): self
    {
        return new self($this->config, $names, $this->with);
    }
    public function with(string $name, Closure $callback): self
    {
        return new self($this->config, $this->only,
            [...$this->with, $name => $callback]);
    }
    public function connector(): McpConnector
    {
        if ($this->connector === null) {
            $connector = McpConnector::make($this->config)
                ->only($this->only);
            foreach ($this->with as $name => $callback) {
                $connector->with($name,
                    fn (McpTool $tool) => $callback($tool, $connector));
            }
            $this->connector = $connector;
        }
        return $this->connector;
    }
    /** @return list<\NeuronAI\Tools\ToolInterface> */
    public function tools(): array
    {
        return $this->connector()->tools();
    }
}
