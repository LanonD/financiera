<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileSyncOperation extends Model
{
    protected $fillable = [
        'user_id',
        'client_operation_id',
        'type',
        'status',
        'payload',
        'result',
        'error',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'processed_at' => 'datetime',
    ];
}
