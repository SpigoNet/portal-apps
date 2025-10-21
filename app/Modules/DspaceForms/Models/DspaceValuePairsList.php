<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DspaceValuePairsList extends Model
{
    use HasFactory;

    protected $table = 'dspace_value_pairs_lists';
    protected $fillable = ['name', 'dc_term'];

    public function pairs(): HasMany
    {
        return $this->hasMany(DspaceValuePair::class, 'list_id')->orderBy('order');
    }
}
