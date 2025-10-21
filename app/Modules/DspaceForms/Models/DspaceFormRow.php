<?php

namespace App\Modules\DspaceForms\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DspaceFormRow extends Model
{
    use HasFactory;
    protected $fillable = ['form_id', 'order'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(DspaceForm::class, 'form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DspaceFormField::class, 'row_id')->orderBy('order');
    }

    public function relationFields(): HasMany
    {
        return $this->hasMany(DspaceRelationField::class, 'row_id')->orderBy('order');
    }
}
