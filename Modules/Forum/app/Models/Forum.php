<?php

namespace Modules\Forum\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Forum extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'content',
        'image_url',
        'posted_by_id',
    ];

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ForumComment::class, 'forum_id');
    }

    public function forumLikes(): HasMany
    {
        return $this->hasMany(ForumLike::class, 'forum_id');
    }
}
