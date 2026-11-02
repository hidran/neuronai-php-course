<?php
declare(strict_types=1);
namespace App\Console\Commands;
use Illuminate\Console\Command;
use NeuronAI\Laravel\Facades\AIProvider;
use NeuronAI\Laravel\Facades\VectorStore;
use NeuronAI\Laravel\VectorStoreManager;
use NeuronAI\RAG\VectorStore\MemoryVectorStore;
use Throwable;
final class Components extends Command
{
    protected $signature = 'components {check}';
    protected $description = 'Inspect the SDK component managers';
    public function handle(): int
    {
        if ($this->argument('check') === 'provider') {
            $first = AIProvider::driver();
            $second = AIProvider::driver();
            $same = json_encode($first === $second);
            $this->line('Driver: ' . AIProvider::getDefaultDriver());
            $this->line('Class: ' . $first::class);
            $this->line("Same object: {$same}");
            return self::SUCCESS;
        }
        $factory = fn () => new MemoryVectorStore();
        VectorStore::extend('memory', $factory);
        $facade = VectorStore::driver('memory');
        $this->line('Facade: ' . $facade::class);
        try {
            $manager = app(VectorStoreManager::class);
            $direct = $manager->driver('memory');
            $this->line('Container: ' . $direct::class);
        } catch (Throwable $e) {
            $this->error('Container: ' . $e->getMessage());
        }
        return self::SUCCESS;
    }
}
