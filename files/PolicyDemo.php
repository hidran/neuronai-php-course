<?php
declare(strict_types=1);
namespace App\Mcp;
use App\Agents\PolicyAgent;
use Dotenv\Dotenv;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\ToolException;
use NeuronAI\Tools\ToolRegistry;
use RuntimeException;
class PolicyDemo
{
    private static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }
    private static function show(string $label, array $tools): void
    {
        echo $label . ': ' . implode(', ', McpPolicy::names($tools))
            . ' | count ' . count($tools) . PHP_EOL;
    }
    public static function compare(): void
    {
        $old = McpPolicy::connector()->exclude(['maintenance_preview'])
            ->tools();
        $new = McpPolicy::connector(['--expanded'])
            ->exclude(['maintenance_preview'])->tools();
        $allowed = McpPolicy::select(McpPolicy::READS);
        self::show('Exclude before addition', $old);
        self::show('Exclude after addition', $new);
        self::show('Only after addition', $allowed);
        self::check(count($old) === 2 && count($new) === 3,
            'Exclusion comparison did not change.');
        McpPolicy::assertExact($allowed, McpPolicy::READS);
        $emptyOnly = McpPolicy::connector(['--expanded'])->only([])->tools();
        self::show('Connector only empty', $emptyOnly);
        self::check(count($emptyOnly) === 4, 'Unexpected empty-only meaning.');
        McpPolicy::$factories = 0;
        $deny = McpPolicy::select([]);
        self::check($deny === [] && McpPolicy::$factories === 0,
            'Deny-all constructed a connector.');
        echo 'Application deny-all: count 0, connector factories 0' . PHP_EOL;
        echo 'Discovery comparison complete.' . PHP_EOL;
    }
    public static function compatibility(): void
    {
        $typo = ['get_office_hour', 'list_locations'];
        $tools = McpPolicy::connector()->only($typo)->tools();
        self::show('Literal typo result', $tools);
        self::check(McpPolicy::names($tools) === ['list_locations'],
            'A misspelled name matched unexpectedly.');
        try {
            McpPolicy::assertExact($tools, $typo);
            throw new RuntimeException('Missing name was not detected.');
        } catch (RuntimeException $error) {
            self::check($error->getMessage() === 'Required MCP capability missing.',
                'The wrong check failed.');
        }
        echo 'Missing required name detected: yes' . PHP_EOL;
        $selected = McpPolicy::connector(['--unsupported'])
            ->only(McpPolicy::READS)->tools();
        McpPolicy::assertExact($selected, McpPolicy::READS);
        echo 'Unrelated unsupported schema filtered: yes' . PHP_EOL;
        foreach ([['--unsupported'], ['--broken-read']] as $flags) {
            $rejected = false;
            try {
                $connector = McpPolicy::connector($flags);
                if ($flags === ['--broken-read']) {
                    $connector->only(['get_office_hours']);
                }
                $connector->tools();
            } catch (ToolException) {
                $rejected = true;
            }
            self::check($rejected, 'Unsupported schema was accepted.');
        }
        echo 'Unfiltered and selected broken schemas rejected: yes' . PHP_EOL;
        echo 'Compatibility checks complete.' . PHP_EOL;
    }
    public static function permissions(): void
    {
        $tools = McpPolicy::connector(['--north-only'])
            ->only(['get_office_hours', 'maintenance_preview'])->tools();
        $byName = [];
        foreach ($tools as $tool) {
            $byName[$tool->getName()] = $tool;
        }
        $good = $byName['get_office_hours'](location: 'north');
        $outside = $byName['get_office_hours'](location: 'south');
        $invalid = $byName['get_office_hours'](location: 42);
        $extra = $byName['get_office_hours'](location: 'north', account: 'demo');
        $maintenance = $byName['maintenance_preview']();
        self::check(!$good->isError() && $outside->isError()
            && $invalid->isError() && $extra->isError()
            && $maintenance->isError(), 'Server permission checks differ.');
        echo 'Trusted fixture scope: north' . PHP_EOL;
        echo 'North accepted; south rejected by server: yes' . PHP_EOL;
        echo 'Wrong type and extra argument rejected: yes' . PHP_EOL;
        echo 'Read-only identity denied maintenance: yes' . PHP_EOL;
        echo 'Permission checks complete.' . PHP_EOL;
    }
    public static function metadata(): void
    {
        $before = McpPolicy::connector()->only(McpPolicy::READS)->tools();
        $after = McpPolicy::connector(['--changed-description'])
            ->only(McpPolicy::READS)->tools();
        McpPolicy::assertExact($before, McpPolicy::READS);
        McpPolicy::assertExact($after, McpPolicy::READS);
        self::check($before[0]->getDescription() !== $after[0]->getDescription(),
            'Description did not change.');
        echo 'Allowed names unchanged: yes' . PHP_EOL;
        echo 'Description changed under allowed name: yes' . PHP_EOL;
        echo 'Names do not pin descriptions or behavior.' . PHP_EOL;
        echo 'Metadata checks complete.' . PHP_EOL;
    }
    public static function approval(): void
    {
        $tools = McpPolicy::approvalPreview();
        McpPolicy::assertExact($tools, ['maintenance_preview']);
        self::check($tools[0]->requiresApproval() === true,
            'Approval callback was not applied.');
        echo 'Approval flag configured: yes' . PHP_EOL;
        echo 'Maintenance invoked: no' . PHP_EOL;
        echo 'Pause/resume application implemented: no' . PHP_EOL;
        echo 'Approval configuration complete.' . PHP_EOL;
    }
    public static function collisions(): void
    {
        $first = McpPolicy::select(McpPolicy::READS);
        $second = McpPolicy::select(['get_office_hours']);
        $rejected = false;
        try {
            new ToolRegistry([...$first, ...$second]);
        } catch (ToolException) {
            $rejected = true;
        }
        self::check($rejected, 'Duplicate names were not rejected.');
        $second = array_filter($second, fn ($tool): bool =>
            !in_array($tool->getName(), McpPolicy::names($first), true));
        $registry = new ToolRegistry([...$first, ...$second]);
        McpPolicy::assertExact($registry->all(), McpPolicy::READS);
        echo 'Duplicate names rejected before inference: yes' . PHP_EOL;
        self::show('Resolved registry', $registry->all());
        echo 'Collision checks complete.' . PHP_EOL;
    }
    public static function question(string $location, bool $both = false): void
    {
        $root = dirname(__DIR__, 2);
        Dotenv::createImmutable($root)->load();
        $audit = $root . '/storage/mcp-policy.jsonl';
        file_put_contents($audit, '');
        self::show('Offered tools', McpPolicy::select(McpPolicy::READS));
        $prompt = ($both ? 'Use list_locations first. Then ' : '')
            . 'use get_office_hours for ' . $location
            . '. Report exact hours and timezone.';
        $state = PolicyAgent::make()
            ->setThreadId('policy-' . bin2hex(random_bytes(8)))
            ->chat(new UserMessage($prompt));
        self::check($state->getToolRuns('get_office_hours') >= 1,
            'The remote read did not run.');
        if ($both) {
            self::check($state->getToolRuns('list_locations') >= 1,
                'The distinct exercise did not list locations.');
        }
        $reply = $state->getMessage()?->getContent() ?? '';
        $hours = $location === 'north' ? ['09:00', '17:00'] : ['10:00', '18:00'];
        foreach ([...$hours, 'Europe/Rome'] as $fact) {
            self::check(str_contains($reply, $fact), 'Server fact is missing.');
        }
        echo $reply . PHP_EOL;
        $entries = file($audit, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        self::check($entries !== false && $entries !== [], 'No audited call.');
        foreach ($entries as $line) {
            $entry = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
            self::check(in_array($entry['name'], McpPolicy::READS, true),
                'An unapproved capability ran.');
            if ($entry['name'] === 'get_office_hours') {
                self::check($entry['arguments'] === ['location' => $location],
                    'The invocation carried unexpected data.');
            } else {
                self::check($entry['arguments'] === [],
                    'The no-argument read carried context.');
            }
            echo 'Payload: ' . json_encode($entry) . PHP_EOL;
        }
        echo 'Read-only agent verified.' . PHP_EOL;
    }
    public static function run(string $mode): void
    {
        match ($mode) {
            '--compare' => self::compare(),
            '--compatibility' => self::compatibility(),
            '--permissions' => self::permissions(),
            '--metadata' => self::metadata(),
            '--invoke' => self::question('north'),
            '--approval' => self::approval(),
            '--collisions' => self::collisions(),
            default => throw new RuntimeException('Unknown demo mode.'),
        };
    }
}
