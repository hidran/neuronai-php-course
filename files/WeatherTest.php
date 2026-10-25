<?php
declare(strict_types=1);
use App\Deterministic\StubWeatherClient;
use App\Deterministic\WeatherTool;
use PHPUnit\Framework\TestCase;
final class WeatherTest extends TestCase
{
    public function testMappingPreservesCoordinates(): void
    {
        $calls = new ArrayObject();
        $tool = new WeatherTool(new StubWeatherClient($calls));
        self::assertSame('14 Celsius', $tool(45.07, 7.69));
        self::assertSame([[45.07, 7.69]], $calls->getArrayCopy());
    }
    public function testMissingTemperatureFailsClearly(): void
    {
        $tool = new WeatherTool(
            new StubWeatherClient(new ArrayObject(), [])
        );
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Temperature unavailable.');
        $tool(45.07, 7.69);
    }
}
