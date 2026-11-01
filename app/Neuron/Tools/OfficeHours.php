<?php
declare(strict_types=1);
namespace App\Neuron\Tools;
use Closure;
use NeuronAI\Tools\Tool;
final class OfficeHours extends Tool
{
    protected string $name = 'office_hours';
    protected ?string $description = 'Returns the support office\'s'
        . ' published hours. No account access, no changes.';
    public function __construct(
        private readonly ?Closure $trace = null,
    ) {
    }
    #[\Override]
    protected function properties(): array
    {
        return [];
    }
    public function __invoke(): string
    {
        if ($this->trace !== null) {
            ($this->trace)('[office_hours] tool ran');
        }
        return 'Monday to Friday, 9:00 to 17:00 CET.'
            . ' Closed on public holidays.';
    }
}
