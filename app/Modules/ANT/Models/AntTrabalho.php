<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;

class AntTrabalho extends Model
{
    protected $table = 'ant_trabalhos';

    protected $fillable = [
        'semestre', 'nome', 'descricao', 'dicas_correcao',
        'materia_id', 'tipo_trabalho_id', 'tipos_trabalho_ids', 'prazo', 'maximo_alunos', 'peso_id'
    ];

    protected $casts = [
        'prazo' => 'date',
        'tipos_trabalho_ids' => 'array',
    ];

    public function materia()
    {
        return $this->belongsTo(AntMateria::class, 'materia_id');
    }

    public function tipoTrabalho()
    {
        return $this->belongsTo(AntTipoTrabalho::class, 'tipo_trabalho_id');
    }

    /**
     * Returns all accepted file extensions for this assignment.
     * Uses tipos_trabalho_ids (new multi-select) when set,
     * falling back to tipo_trabalho_id (legacy single select).
     *
     * Result is memoized per model instance to avoid repeated queries.
     *
     * @return string[]  Uppercase extension strings, e.g. ['PDF', 'ZIP', 'LINK']
     */
    public function getAllowedExtensions(): array
    {
        if (isset($this->cachedAllowedExtensions)) {
            return $this->cachedAllowedExtensions;
        }

        if (!empty($this->tipos_trabalho_ids)) {
            $tipos = AntTipoTrabalho::whereIn('id', $this->tipos_trabalho_ids)->get();
        } else {
            $tipos = collect([$this->tipoTrabalho])->filter();
        }

        $extensions = [];
        foreach ($tipos as $tipo) {
            foreach (explode('|', strtoupper($tipo->arquivos)) as $ext) {
                $ext = trim($ext);
                if ($ext !== '') {
                    $extensions[] = $ext;
                }
            }
        }

        return $this->cachedAllowedExtensions = array_values(array_unique($extensions));
    }

    /** @var string[]|null Cached result for getAllowedExtensions() */
    private ?array $cachedAllowedExtensions = null;

    public function peso()
    {
        return $this->belongsTo(AntPeso::class, 'peso_id');
    }

    public function entregas()
    {
        return $this->hasMany(AntEntrega::class, 'trabalho_id');
    }

    // Se o trabalho for uma prova vinculada
    public function prova()
    {
        return $this->hasOne(AntProva::class, 'trabalho_id');
    }
}
