<?php
declare(strict_types=1);
namespace App\Mcp;
final readonly class PolicyFixture
{
    /** @param list<string> $flags */
    public function __construct(private array $flags)
    {
    }
    private function flag(string $name): bool
    {
        return in_array($name, $this->flags, true);
    }
    public function definitions(): array
    {
        $hours = [
            'name' => 'get_office_hours',
            'description' => $this->flag('--changed-description')
                ? 'Read the reviewed office schedule; revised wording.'
                : 'Read office hours for north or south.',
            'inputSchema' => [
                'type' => 'object',
                'properties' => ['location' => [
                    'type' => 'string',
                    'description' => 'The office: north or south.',
                ]],
                'required' => ['location'],
                'additionalProperties' => false,
            ],
            'annotations' => ['readOnlyHint' => true],
        ];
        if ($this->flag('--broken-read')) {
            $hours['inputSchema']['anyOf'] = [['type' => 'object']];
        }
        $tools = [$hours, [
            'name' => 'list_locations',
            'description' => 'List locations allowed for this caller.',
            'inputSchema' => ['type' => 'object',
                'additionalProperties' => false],
            'annotations' => ['readOnlyHint' => true],
        ], $this->maintenance('maintenance_preview')];
        if ($this->flag('--expanded')) {
            $tools[] = $this->maintenance('new_maintenance_preview');
        }
        if ($this->flag('--unsupported')) {
            $tool = $this->maintenance('complex_maintenance');
            $tool['inputSchema']['anyOf'] = [['type' => 'object']];
            $tools[] = $tool;
        }
        return $tools;
    }
    private function maintenance(string $name): array
    {
        return [
            'name' => $name,
            'description' => 'Teaching maintenance capability, no action',
            'inputSchema' => ['type' => 'object',
                'additionalProperties' => false],
            'annotations' => ['readOnlyHint' => false],
        ];
    }
    public function call(string $name, array $arguments): array
    {
        if (str_contains($name, 'maintenance')) {
            return $this->result('Read-only identity: action denied.',
                true);
        }
        $scope = $this->flag('--north-only')
            ? ['north'] : ['north', 'south'];
        if ($name === 'list_locations') {
            return $arguments === []
                ? $this->result(['locations' => $scope])
                : $this->result('No arguments accepted.', true);
        }
        $location = $arguments['location'] ?? null;
        if (!is_string($location)
            || array_keys($arguments) !== ['location']) {
            return $this->result('A location string is required.',
                true);
        }
        if (!in_array($location, $scope, true)) {
            return $this->result('Location outside caller scope.',
                true);
        }
        $hours = ['north' => '09:00-17:00', 'south' => '10:00-18:00'];
        return $this->result([
            'location' => $location, 'hours' => $hours[$location],
            'timezone' => 'Europe/Rome',
        ]);
    }
    private function result(array|string $value, bool $error = false)
        : array
    {
        return ['content' => [['type' => 'text',
            'text' => is_string($value) ? $value : json_encode($value),
        ]], 'isError' => $error];
    }
}
