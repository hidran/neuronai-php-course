<?php
declare(strict_types=1);
namespace App\Deterministic;
use NeuronAI\HttpClient\HttpClientInterface;
use NeuronAI\HttpClient\HttpRequest;
use RuntimeException;
final readonly class HttpWeatherClient implements WeatherClient
{
    public function __construct(private HttpClientInterface $http)
    {
    }
    #[\Override]
    public function current(float $latitude, float $longitude): array
    {
        $query = http_build_query([
            'latitude' => $latitude, 'longitude' => $longitude,
            'current' => 'temperature_2m',
            'temperature_unit' => 'celsius',
        ]);
        $request = HttpRequest::get(
            'https://api.open-meteo.com/v1/forecast?' . $query
        );
        $response = $this->http->request($request);
        if (!$response->isSuccessful()) {
            throw new RuntimeException('Weather request failed.');
        }
        $data = $response->json();
        return ['temperature' =>
            $data['current']['temperature_2m'] ?? null];
    }
}
