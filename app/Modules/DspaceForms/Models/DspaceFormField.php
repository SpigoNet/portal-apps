<?php

namespace App\Modules\DspaceForms\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DspaceFormField extends Model
{
    use HasFactory;

    protected $fillable = [
        'row_id', 'dc_schema', 'dc_element', 'dc_qualifier', 'repeatable',
        'label', 'input_type', 'hint', 'required', 'style', 'vocabulary',
        'vocabulary_closed', 'value_pairs_name', 'order',
    ];

    protected $casts = [
        'repeatable' => 'boolean',
        'vocabulary_closed' => 'boolean',
    ];

    public function row(): BelongsTo
    {
        return $this->belongsTo(DspaceFormRow::class, 'row_id');
    }
}

