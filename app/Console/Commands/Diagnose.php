<?php
declare(strict_types=1);
namespace App\Console\Commands;
use App\Neuron\Agents\AssistantAgent;
use App\Neuron\Output\SupportHours;
use App\Neuron\Tools\OfficeHours;
use Illuminate\Console\Command;
use NeuronAI\Chat\Messages\Stream\Chunks\TextChunk;
use NeuronAI\Chat\Messages\UserMessage;
use NeuronAI\Exceptions\AgentException;
use NeuronAI\Laravel\Facades\Neuron;
use NeuronAI\UniqueIdGenerator;
final class Diagnose extends Command
{
    protected $signature = 'diagnose {path}';
    protected $description = 'Try one way of calling an agent';
    public function handle(): int
    {
        $path = $this->argument('path');
        $question = new UserMessage(
            'When is support open? Check the published hours.',
        );
        if ($path === 'facade') {
            try {
                Neuron::chat($question);
                $this->info('The facade answered.');
            } catch (AgentException $e) {
                $this->error('Facade: ' . $e->getMessage());
            }
            return self::SUCCESS;
        }
        $agent = app(AssistantAgent::class)
            ->for(UniqueIdGenerator::generateId("{$path}_"));
        if ($path === 'stream') {
            $stream = $agent->stream($question);
            foreach ($stream as $part) {
                echo $part instanceof TextChunk ? $part->content : '';
            }
            $this->line(PHP_EOL . $stream->getReturn()::class);
            return self::SUCCESS;
        }
        if ($path === 'structured') {
            $hours = $agent
                ->structured($question, SupportHours::class);
            $this->line("From {$hours->opens} to {$hours->closes}");
            return self::SUCCESS;
        }
        if ($path === 'tool') {
            $agent->addTool(new OfficeHours($this->line(...)));
        }
        $state = $agent->chat($question);
        $this->line('Answer: ' . $state->getMessage()?->getContent());
        return self::SUCCESS;
    }
}
