<?php
declare(strict_types=1);
namespace App\Models;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;
    protected $fillable = ['user_id', 'title'];
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    /** The agent thread of this conversation: user and record. */
    public function threadId(): string
    {
        return "user-{$this->user_id}:conversation-{$this->id}";
    }
}
