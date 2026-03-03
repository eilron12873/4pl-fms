<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    protected $table = 'activities';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'event',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public static function log(
        string $description,
        ?string $event = null,
        ?Model $subject = null,
        ?Model $causer = null,
        array $properties = [],
        string $logName = 'default',
    ): self {
        return static::create([
            'log_name' => $logName,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'causer_type' => $causer?->getMorphClass(),
            'causer_id' => $causer?->getKey(),
            'properties' => $properties,
            'event' => $event,
        ]);
    }
}

