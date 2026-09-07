<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    protected $table = 'yomi_sync_logs';

    protected $fillable = [
        'manga_id',
        'provider',
        'operation',
        'status',
        'error_type',
        'error_message',
        'http_status',
        'attempt',
        'started_at',
        'finished_at',
        'duration_ms',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'attempt' => 'integer',
            'duration_ms' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function manga(): BelongsTo
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }
}
