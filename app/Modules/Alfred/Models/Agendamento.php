<?php

namespace App\Modules\Alfred\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agendamento extends Model
{
    use HasFactory;

    protected $table = 'alfred_mensagens_agendadas';

    protected $fillable = [
        'persona_id',
        'mensagem',
        'intervalo_minutos',
        'hora_inicio',
        'hora_fim',
        'dias_semana',
        'ativa',
        'ultimo_envio_at',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'ativa' => 'boolean',
        'ultimo_envio_at' => 'datetime',
    ];

    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    public function deveEnviarAgora(): bool
    {
        if (! $this->ativa) {
            return false;
        }

        $agora = now();

        $diaSemana = (int) $agora->dayOfWeekIso;
        if (! in_array($diaSemana, $this->dias_semana ?? [])) {
            return false;
        }

        $horaAtual = $agora->format('H:i');
        if ($horaAtual < $this->hora_inicio || $horaAtual > $this->hora_fim) {
            return false;
        }

        if ($this->ultimo_envio_at) {
            $minutosDesdeUltimo = $agora->diffInMinutes($this->ultimo_envio_at);
            if ($minutosDesdeUltimo < $this->intervalo_minutos) {
                return false;
            }
        }

        return true;
    }

    public function marcarEnviado(): void
    {
        $this->update(['ultimo_envio_at' => now()]);
    }
}
