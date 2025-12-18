<?php

namespace Modules\Collaboration\Models;

use App\Models\CollaborationField;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Collaboration extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'content',
        'image_url',
        'posted_by_id',
        'collaboration_field_id',
    ];

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function collaborationField(): BelongsTo
    {
        return $this->belongsTo(CollaborationField::class);
    }
}
