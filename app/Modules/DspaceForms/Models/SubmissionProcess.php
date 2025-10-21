<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubmissionProcess extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function steps(): HasMany
    {
        return $this->hasMany(SubmissionStep::class)->orderBy('order');
    }
}
