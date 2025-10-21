<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DspaceForm extends Model
{
    use HasFactory;
    protected $fillable = ['name'];

    public function rows(): HasMany
    {
        return $this->hasMany(DspaceFormRow::class, 'form_id')->orderBy('order');
    }
}

