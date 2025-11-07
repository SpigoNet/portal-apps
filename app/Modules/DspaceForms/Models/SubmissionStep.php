<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubmissionStep extends Model
{
    use HasFactory;
    protected $table = 'dspace_submission_steps';
    protected $fillable = ['submission_process_id', 'step_id', 'order'];

    public function process(): BelongsTo
    {
        return $this->belongsTo(SubmissionProcess::class, 'submission_process_id');
    }
}
