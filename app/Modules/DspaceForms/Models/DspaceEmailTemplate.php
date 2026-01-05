<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DspaceEmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'dspace_email_templates';

    protected $fillable = [
        'xml_configuration_id',
        'name',
        'subject', // Campo para facilitar a visualização do assunto
        'content',
        'description',
    ];

    public function configuration(): BelongsTo
    {
        return $this->belongsTo(DspaceXmlConfiguration::class, 'xml_configuration_id');
    }
}
