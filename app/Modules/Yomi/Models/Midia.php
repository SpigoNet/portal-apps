<?php

namespace App\Modules\Yomi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Midia extends Model
{
    protected $table = 'yomi_midias';

    protected $fillable = [
        'entity_type',
        'entity_id',
        'type',
        'source_url',
        'storage_path',
        'mime_type',
        'width',
        'height',
        'checksum',
        'status',
        'downloaded_at',
        'error_message',
        'attempt',
    ];

    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'attempt' => 'integer',
            'downloaded_at' => 'datetime',
        ];
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    public function isDownloaded(): bool
    {
        return $this->status === 'baixada';
    }

    public function displayUrl(): ?string
    {
        $disk = (string) config('yomi.media.disk', 'local');

        if ($this->storage_path !== null && Storage::disk($disk)->exists($this->storage_path)) {
            return Storage::disk($disk)->url($this->storage_path);
        }

        return $this->source_url;
    }
}
