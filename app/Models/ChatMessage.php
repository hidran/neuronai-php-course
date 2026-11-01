<?php
declare(strict_types=1);
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
final class ChatMessage extends Model
{
    protected $fillable = [
        'thread_id',
        'message_id',
        'role',
        'content',
        'meta',
    ];
    #[\Override]
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'meta' => 'array',
        ];
    }
}
