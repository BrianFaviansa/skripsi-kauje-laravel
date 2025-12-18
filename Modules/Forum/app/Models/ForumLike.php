<?php

namespace Modules\Forum\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForumLike extends Model
{
    use HasUuids;

    protected $fillable = [
        'liked_by_id',
        'forum_id',
    ];

    public function likedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'liked_by_id');
    }

    public function forum(): BelongsTo
    {
        return $this->belongsTo(Forum::class, 'forum_id');
    }
}
