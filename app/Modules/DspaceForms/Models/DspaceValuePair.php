<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DspaceValuePair extends Model
{
    use HasFactory;

    protected $table = 'dspace_value_pairs';
    protected $fillable = ['list_id', 'displayed_value', 'stored_value', 'order'];
    protected $primaryKey = 'id';

    public function list(): BelongsTo
    {
        return $this->belongsTo(DspaceValuePairsList::class, 'list_id');
    }
}
