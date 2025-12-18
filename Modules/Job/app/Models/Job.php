<?php

namespace Modules\Job\Models;

use App\Models\City;
use App\Models\JobField;
use App\Models\Province;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Job extends Model
{
    use HasUuids;

    protected $fillable = [
        'title',
        'content',
        'company',
        'job_type',
        'open_from',
        'open_until',
        'registration_link',
        'image_url',
        'posted_by_id',
        'job_field_id',
        'province_id',
        'city_id',
    ];

    protected function casts(): array
    {
        return [
            'open_from' => 'datetime',
            'open_until' => 'datetime',
        ];
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_id');
    }

    public function jobField(): BelongsTo
    {
        return $this->belongsTo(JobField::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
