<?php

namespace App\Modules\DspaceForms\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Assumimos que SubmissionProcess, DspaceForm, DspaceFormMap e DspaceValuePairsList existem e
// foram atualizados para incluir a FK 'xml_configuration_id'.

class DspaceXmlConfiguration extends Model
{
    use HasFactory;

    // Nome da tabela conforme o padrão solicitado
    protected $table = 'dspace_xml_configurations';

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    /**
     * Define o relacionamento com o usuário que criou a configuração.
     */
    public function user()
    {
        // Assumimos que o Model User está em App\Models\User
        return $this->belongsTo(\App\Models\User::class);
    }

    // --- Relacionamentos para Duplicação em Cascata ---

    public function formMaps()
    {
        return $this->hasMany(DspaceFormMap::class, 'xml_configuration_id');
    }

    public function valuePairsLists()
    {
        // Esta relação será usada para buscar 'dspace_value_pairs_lists'
        return $this->hasMany(DspaceValuePairsList::class, 'xml_configuration_id');
    }

    public function forms()
    {
        // Esta relação será usada para buscar 'dspace_forms'
        return $this->hasMany(DspaceForm::class, 'xml_configuration_id');
    }

    public function submissionProcesses()
    {
        // Esta relação busca 'dspace_submission_processes'
        return $this->hasMany(SubmissionProcess::class, 'xml_configuration_id');
    }

    /**
     * Define o relacionamento com os templates de e-mail.
     */
    public function emailTemplates()
    {
        // Esta relação busca 'dspace_email_templates'
        return $this->hasMany(DspaceEmailTemplate::class, 'xml_configuration_id');
    }
}
