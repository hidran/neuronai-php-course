<?php
declare(strict_types=1);
namespace App\Agents;
use App\Mcp\HttpOfficeConnection;
use App\PackageVersionTool;
class HttpOfficeHoursAgent extends OfficeHoursAgent
{
    private ?string $endpoint = null;
    public function endpoint(string $url): static
    {
        $this->endpoint = $url;
        return $this;
    }
    protected function tools(): array
    {
        $connector = HttpOfficeConnection::connector($this->endpoint);
        return [new PackageVersionTool(), ...$connector->tools()];
    }
}
