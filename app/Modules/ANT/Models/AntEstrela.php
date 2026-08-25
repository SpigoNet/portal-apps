<?php

namespace App\Modules\ANT\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AntEstrela extends Model
{
    protected $table = 'ant_estrelas';

    protected $fillable = [
        'semestre', 'materia_id', 'aluno_ra',
        'apresentacao_id', 'agendamento_id',
    ];

    public function materia()
    {
        return $this->belongsTo(AntMateria::class, 'materia_id');
    }

    public function aluno()
    {
        return $this->belongsTo(AntAluno::class, 'aluno_ra', 'ra');
    }

    public function apresentacao()
    {
        return $this->belongsTo(AntApresentacao::class, 'apresentacao_id');
    }

    public function agendamento()
    {
        return $this->belongsTo(AntApresentacaoAgendamento::class, 'agendamento_id');
    }

    /**
     * Rank de estrelas por aluno para a matéria/semestre.
     * Inclui todos os alunos matriculados (com 0 estrelas também).
     * Retorna Collection de objetos: aluno, ra, estrelas, posicao.
     */
    public static function rankPorMateria(int $materiaId, string $semestre): Collection
    {
        $estrelas = self::where('materia_id', $materiaId)
            ->where('semestre', $semestre)
            ->get();

        $contagem = $estrelas->groupBy('aluno_ra')->map->count();

        $alunos = AntAluno::whereHas('materias', function ($q) use ($materiaId, $semestre) {
            $q->where('ant_materias.id', $materiaId)
                ->where('ant_aluno_materia.semestre', $semestre);
        })->get();

        $rank = $alunos->map(function ($aluno) use ($contagem) {
            return (object) [
                'aluno' => $aluno,
                'ra' => $aluno->ra,
                'estrelas' => $contagem->get($aluno->ra, 0),
            ];
        })->sort(function ($a, $b) {
            if ($b->estrelas === $a->estrelas) {
                return strcmp($a->aluno->nome, $b->aluno->nome);
            }

            return $b->estrelas <=> $a->estrelas;
        })->values();

        $posicao = 0;
        $anterior = null;
        foreach ($rank as $i => $item) {
            if ($anterior === null || $item->estrelas !== $anterior) {
                $posicao = $i + 1;
                $anterior = $item->estrelas;
            }
            $item->posicao = $posicao;
        }

        return $rank;
    }
}
