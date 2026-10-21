<?php
declare(strict_types=1);
namespace App\Tools;
use JsonException;
use NeuronAI\Exceptions\HttpException;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\HttpClient\HttpRequest;
use NeuronAI\Tools\PropertyType;
use NeuronAI\Tools\Tool;
use NeuronAI\Tools\ToolOutput;
use NeuronAI\Tools\ToolProperty;
class WeatherTool extends Tool
{
    private const string PLACES =
        'https://geocoding-api.open-meteo.com/v1/search';
    private const string WEATHER =
        'https://api.open-meteo.com/v1/forecast';
    protected string $name = 'get_current_temperature';
    protected ?string $description = 'Returns the temperature'
        . ' right now in a city, in degrees Celsius, from live'
        . ' weather data. Use it whenever the user asks about the'
        . ' current temperature in Italy. One city per'
        . ' call. Never guess a temperature: always call this tool.';
    public function __construct(private HttpClientInterface $http)
    {
    }
    protected function properties(): array
    {
        return [
            new ToolProperty(
                name: 'city',
                type: PropertyType::STRING,
                description: 'An Italian city name only, like Turin.',
                required: true,
            ),
        ];
    }
    public function __invoke(string $city): ToolOutput
    {
        try {
            $found = $this->get(self::PLACES, [
                'name' => $city, 'count' => 1, 'language' => 'en',
                'countryCode' => 'IT',
            ]);
            $place = $found['results'][0] ?? null;
            if ($place === null) {
                return ToolOutput::error("No city named {$city}."
                    . ' Tell the user, and do not retry.');
            }
            $weather = $this->get(self::WEATHER, [
                'latitude' => $place['latitude'],
                'longitude' => $place['longitude'],
                'current' => 'temperature_2m',
                'temperature_unit' => 'celsius',
            ]);
        } catch (HttpException|JsonException) {
            return ToolOutput::error('The weather service is down.'
                . ' Tell the user, and do not retry.');
        }
        $celsius = $weather['current']['temperature_2m'] ?? null;
        if (!is_int($celsius) && !is_float($celsius)) {
            return ToolOutput::error('No temperature available.'
                . ' Tell the user, and do not retry.');
        }
        return ToolOutput::text("{$place['name']},"
            . " {$place['country']}: {$celsius} degrees Celsius");
    }
    private function get(string $url, array $query): array
    {
        $request = HttpRequest::get(
            $url . '?' . http_build_query($query),
        );
        return $this->http->request($request)->json();
    }
}
