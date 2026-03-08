<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationLog extends Model
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_POSTED = 'posted';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'event_type',
        'idempotency_key',
        'source_system',
        'source_reference',
        'status',
        'message',
        'journal_id',
    ];

    protected $casts = [
        'journal_id' => 'integer',
    ];
}
