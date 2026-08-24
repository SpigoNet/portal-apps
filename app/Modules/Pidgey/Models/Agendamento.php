<?php

namespace App\Modules\Pidgey\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Agendamento extends Model
{
    use HasFactory;

    protected $table = 'pidgey_agendamentos';

    protected $fillable = [
        'user_id',
        'persona_slug',
        'canal',
        'mensagem',
        'interpretar',
        'frequencia',
        'hora',
        'dia_semana',
        'proxima_execucao',
        'ativo',
    ];

    protected $casts = [
        'interpretar' => 'boolean',
        'ativo' => 'boolean',
        'dia_semana' => 'integer',
        'proxima_execucao' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('user', function (Builder $query) {
            if (Auth::check()) {
                $query->where('user_id', Auth::id());
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function deveEnviarAgora(): bool
    {
        if (! $this->ativo) {
            return false;
        }

        if (! $this->proxima_execucao) {
            return false;
        }

        return $this->proxima_execucao->lte(now());
    }

    /**
     * Calcula a próxima ocorrência com base na frequência.
     * Para 'una_vez' mantém a data já definida.
     */
    public function calcularProximaExecucao(?\Carbon\Carbon $base = null): ?\Carbon\Carbon
    {
        $base = $base ?? now();

        if ($this->frequencia === 'una_vez') {
            return $this->proxima_execucao;
        }

        $hora = $this->hora ?? '09:00';

        if ($this->frequencia === 'diario') {
            $next = $base->copy()->setTimeFromTimeString($hora);
            if ($next->lte($base)) {
                $next->addDay();
            }

            return $next;
        }

        if ($this->frequencia === 'semanal') {
            $dia = (int) $this->dia_semana;
            $next = $base->copy()->setTimeFromTimeString($hora);
            while ($next->dayOfWeek !== $dia) {
                $next->addDay();
            }
            if ($next->lte($base)) {
                $next->addWeek();
            }

            return $next;
        }

        return null;
    }

    public function getFrequenciaLabelAttribute(): string
    {
        return match ($this->frequencia) {
            'una_vez' => 'Uma vez',
            'diario' => 'Diário às '.($this->hora ?? '09:00'),
            'semanal' => 'Semanal ('.$this->diaSemanaLabel().') às '.($this->hora ?? '09:00'),
            default => (string) $this->frequencia,
        };
    }

    private function diaSemanaLabel(): string
    {
        $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

        return $dias[$this->dia_semana] ?? '—';
    }

    public function recalcularProximaExecucao(): void
    {
        if ($this->frequencia === 'una_vez') {
            $this->proxima_execucao = null;
            $this->ativo = false;
            $this->save();

            return;
        }

        $this->proxima_execucao = $this->calcularProximaExecucao(now());
        $this->save();
    }
}
