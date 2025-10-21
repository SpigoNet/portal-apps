<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DspaceRelationField extends Model
{
    use HasFactory;

    protected $fillable = [
        'row_id', 'relationship_type', 'search_configuration', 'label', 'hint', 'repeatable', 'order',
    ];

    protected $casts = [
        'repeatable' => 'boolean',
    ];

    public function row(): BelongsTo
    {
        return $this->belongsTo(DspaceFormRow::class, 'row_id');
    }
}
