<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Province extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
    ];

    /**
     * Get the cities that belong to this province.
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    /**
     * Get the users that belong to this province.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
