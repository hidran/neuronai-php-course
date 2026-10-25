<?php
declare(strict_types=1);
$lab = $argv[1] ?? throw new RuntimeException('lab path');
require $lab . '/vendor/autoload.php';
require __DIR__ . '/WeatherTool.php';
require __DIR__ . '/WeatherAgent.php';
use App\Agents\WeatherAgent;
use App\Tools\WeatherTool;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\HttpClient\HttpRequest;
use NeuronAI\HttpClient\HttpResponse;
use NeuronAI\HttpClient\StreamInterface;
use NeuronAI\Testing\FakeAIProvider;
final class StubHttp implements HttpClientInterface
{
    public array $urls = [];
    public function request(HttpRequest $request): HttpResponse
    {
        $this->urls[] = $request->uri;
        $body = str_contains($request->uri, 'geocoding')
            ? '{"results":[{"name":"Turin","country":"Italy","latitude":45.07,"longitude":7.69}]}'
            : '{"current":{"temperature_2m":14.0}}';
        return new HttpResponse(200, $body);
    }
    public function stream(HttpRequest $request): StreamInterface
    {
        return new class implements StreamInterface {
            public function eof(): bool { return true; }
            public function read(int $length): string { return ''; }
            public function readLine(): string { return ''; }
            public function close(): void {}
        };
    }
}
$http = new StubHttp();
$tool = new WeatherTool($http);
$out = (string) $tool('Turin');
if ($out !== 'Turin, Italy: 14 degrees Celsius') {
    throw new RuntimeException("tool output: {$out}");
}
if (!str_contains($http->urls[0], 'countryCode=IT')) {
    throw new RuntimeException('default country not applied');
}
$tool('Paris', 'fr');
if (!str_contains($http->urls[2], 'countryCode=FR')) {
    throw new RuntimeException('country override not applied');
}
$fake = new FakeAIProvider(new AssistantMessage('14 degrees.'));
$agent = WeatherAgent::make($fake, $http)->setThreadId('t');
$names = [];
foreach ($agent->getTools() as $entry) {
    $list = $entry instanceof \NeuronAI\Tools\Toolkits\ToolkitInterface ? $entry->tools() : [$entry];
    foreach ($list as $t) {
        $names[] = $t->getName();
    }
}
if (!in_array('get_current_temperature', $names, true)) {
    throw new RuntimeException('weather tool missing: ' . json_encode($names));
}
$agent->chat(new UserMessage('Average between Turin and Milan?'));
$prompt = (string) $agent->getInstructions()->getContent();
if (!str_contains($prompt, 'TOOLS USAGE RULES')) {
    throw new RuntimeException('tools usage section missing');
}
echo 'WEATHER OK' . PHP_EOL;
