<?php

namespace App\Modules\Alfred\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rotina extends Model
{
    protected $table = 'alfred_rotinas';

    protected $fillable = [
        'user_id',
        'titulo',
        'descricao',
        'tipo_recorrencia',
        'config_recorrencia',
        'horario_sugerido',
        'categoria',
        'ativa',
        'prioridade',
    ];

    protected $casts = [
        'config_recorrencia' => 'array',
        'ativa' => 'boolean',
        'ultima_execucao' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolveRouteBinding($value, $field = null): ?Model
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('user_id', auth()->id())
            ->first();
    }

    public function execucoes()
    {
        return $this->hasMany(RotinaExecucao::class, 'rotina_id');
    }

    public function deveExecutarHoje($data = null)
    {
        $data = $data ? Carbon::parse($data) : now();

        if (! $this->ativa) {
            return false;
        }

        switch ($this->tipo_recorrencia) {
            case 'diaria':
                return true;

            case 'semanal':
                $diasSemana = $this->config_recorrencia['dias_semana'] ?? [];
                $hoje = (int) $data->dayOfWeek;
                $diasSemana = array_map('intval', $diasSemana);

                return in_array($hoje, $diasSemana);

            case 'mensal':
                $diaMes = $this->config_recorrencia['dia_mes'] ?? 1;

                return $data->day == $diaMes;

            case 'unica':
                $dataUnica = $this->config_recorrencia['data'] ?? null;
                if (! $dataUnica) {
                    return false;
                }

                return $data->format('Y-m-d') == $dataUnica;

            default:
                return false;
        }
    }

    public function foiExecutadaHoje($data = null)
    {
        $data = $data ? Carbon::parse($data)->format('Y-m-d') : now()->format('Y-m-d');

        return $this->execucoes()
            ->whereDate('data_execucao', $data)
            ->exists();
    }

    public function marcarExecutada($observacao = null, $data = null)
    {
        $data = $data ? Carbon::parse($data) : now();

        $execucao = $this->execucoes()->create([
            'data_execucao' => $data->format('Y-m-d'),
            'hora_execucao' => now()->format('H:i:s'),
            'observacao' => $observacao,
        ]);

        $this->update(['ultima_execucao' => now()]);

        return $execucao;
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativa', true);
    }

    public function scopeDoUsuario($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeParaHoje($query, $data = null)
    {
        $data = $data ? Carbon::parse($data) : now();

        return $query->ativas()
            ->orderBy('prioridade', 'desc')
            ->orderBy('horario_sugerido', 'asc')
            ->get()
            ->filter(function ($rotina) use ($data) {
                return $rotina->deveExecutarHoje($data);
            });
    }

    public static function pendentesHoje($data = null)
    {
        $data = $data ? Carbon::parse($data) : now();

        return self::ativas()
            ->orderBy('prioridade', 'desc')
            ->orderBy('horario_sugerido', 'asc')
            ->get()
            ->filter(function ($rotina) use ($data) {
                return $rotina->deveExecutarHoje($data) && ! $rotina->foiExecutadaHoje($data);
            });
    }

    public static function todasHoje($userId = null, $data = null)
    {
        $data = $data ? Carbon::parse($data) : now();

        $query = self::ativas();
        if ($userId) {
            $query->doUsuario($userId);
        }

        $rotinas = $query->orderBy('prioridade', 'desc')
            ->orderBy('horario_sugerido', 'asc')
            ->get()
            ->filter(function ($rotina) use ($data) {
                if (! $rotina->deveExecutarHoje($data)) {
                    return false;
                }

                if ($rotina->tipo_recorrencia === 'unica') {
                    return ! $rotina->foiExecutadaHoje($data) && ! $rotina->foiPuladaHoje($data);
                }

                return true;
            });

        return $rotinas->map(function ($rotina) use ($data) {
            $rotina->executada_hoje = $rotina->foiExecutadaHoje($data);
            $rotina->pulada_hoje = $rotina->foiPuladaHoje($data);
            $rotina->motivo_pulo = $rotina->getMotivoPuloHoje($data);

            return $rotina;
        });
    }

    public function getCategoriaBadgeAttribute()
    {
        $cores = [
            'saude' => '#e74c3c',
            'trabalho' => '#34495e',
            'lazer' => '#9b59b6',
            'financeiro' => '#27ae60',
            'familia' => '#f39c12',
            'estudo' => '#3498db',
            'outro' => '#95a5a6',
        ];

        return [
            'label' => ucfirst($this->categoria),
            'cor' => $cores[$this->categoria] ?? '#95a5a6',
        ];
    }

    public function getDescricaoRecorrenciaAttribute()
    {
        switch ($this->tipo_recorrencia) {
            case 'diaria':
                return 'Todos os dias';

            case 'semanal':
                $dias = $this->config_recorrencia['dias_semana'] ?? [];
                $nomesDias = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
                $diasNomes = array_map(function ($d) use ($nomesDias) {
                    return $nomesDias[$d] ?? $d;
                }, $dias);

                return 'Toda '.implode(', ', $diasNomes);

            case 'mensal':
                $dia = $this->config_recorrencia['dia_mes'] ?? 1;

                return "Dia {$dia} de cada mês";

            case 'unica':
                $data = $this->config_recorrencia['data'] ?? null;

                return $data ? 'Única vez: '.Carbon::parse($data)->format('d/m/Y') : 'Data única';

            default:
                return $this->tipo_recorrencia;
        }
    }

    public function pulos()
    {
        return $this->hasMany(RotinaPulo::class, 'rotina_id');
    }

    public function foiPuladaHoje($data = null)
    {
        $data = $data ? Carbon::parse($data)->format('Y-m-d') : now()->format('Y-m-d');

        return $this->pulos()
            ->whereDate('data_pulo', $data)
            ->exists();
    }

    public function pularHoje($motivo = null, $data = null)
    {
        $data = $data ? Carbon::parse($data) : now();

        return $this->pulos()->create([
            'data_pulo' => $data->format('Y-m-d'),
            'motivo' => $motivo,
        ]);
    }

    public function getMotivoPuloHoje($data = null)
    {
        $data = $data ? Carbon::parse($data)->format('Y-m-d') : now()->format('Y-m-d');

        $pulo = $this->pulos()
            ->whereDate('data_pulo', $data)
            ->first();

        return $pulo ? $pulo->motivo : null;
    }

    public function desfazerPuloHoje($data = null)
    {
        $data = $data ? Carbon::parse($data)->format('Y-m-d') : now()->format('Y-m-d');

        return $this->pulos()
            ->whereDate('data_pulo', $data)
            ->delete();
    }
}
