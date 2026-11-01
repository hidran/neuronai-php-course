<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $this->identifier($table, 'thread_id');
            $this->identifier($table, 'message_id');
            $table->string('role', 32);
            $table->longText('content');
            $table->longText('meta');
            $table->timestamp('archived_at')->nullable();
            $table->unique(['thread_id', 'message_id']);
            $table->timestamps();
        });
        Schema::create('workflow_store', function (Blueprint $table) {
            $this->identifier($table, 'partition', 510);
            $this->identifier($table, 'key', 510);
            $table->longText('value');
            $table->timestamp('updated_at')->useCurrent();
            $table->primary(['partition', 'key']);
        });
    }
    private function identifier(
        Blueprint $table,
        string $name,
        int $length = 255,
    ): void {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $table->binary($name, $length);
            return;
        }
        $table->string($name, $length);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_store');
        Schema::dropIfExists('chat_messages');
    }
};
