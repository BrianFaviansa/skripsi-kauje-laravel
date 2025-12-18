<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Faculty extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the majors that belong to this faculty.
     */
    public function majors(): HasMany
    {
        return $this->hasMany(Major::class);
    }

    /**
     * Get the users that belong to this faculty.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
