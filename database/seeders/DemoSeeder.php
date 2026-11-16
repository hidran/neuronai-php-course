<?php
declare(strict_types=1);
namespace Database\Seeders;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use NeuronAI\Chat\History\MessageStoreInterface;
use NeuronAI\Chat\Messages\AssistantMessage;
use NeuronAI\Chat\Messages\UserMessage;
final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $hours = Conversation::query()->create([
            'user_id' => $this->demoUser('Nora')->id,
            'title' => 'Opening hours',
        ]);
        Conversation::query()->create([
            'user_id' => $this->demoUser('Marco')->id,
            'title' => 'Invoice copy',
        ]);
        $store = app(MessageStoreInterface::class);
        $thread = $hours->threadId();
        $store->append($thread, new UserMessage(
            'When does support open?',
        ));
        $store->append($thread, new AssistantMessage(
            'We open at nine, Monday to Friday.',
        ));
        $store->append($thread, new UserMessage(
            'And on Saturday?',
        ));
        $store->append($thread, new AssistantMessage(
            'On Saturday we are closed.',
        ));
    }
    /** A local demo account: the address is fictional, never shown. */
    private function demoUser(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => Str::lower($name) . '@example.test',
            'password' => 'support-demo',
        ]);
    }
}
