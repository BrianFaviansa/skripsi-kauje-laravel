<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Major extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'faculty_id',
    ];

    /**
     * Get the faculty that this major belongs to.
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Get the users that belong to this major.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
