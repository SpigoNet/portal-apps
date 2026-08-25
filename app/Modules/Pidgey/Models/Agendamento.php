<?php

namespace App\Modules\Pidgey\Models;

use App\Models\AiModel;
use App\Models\User;
use App\Modules\Admin\Services\AiProviderService;
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
        'intervalo_minutos',
        'hora_inicio',
        'hora_fim',
        'dias_semana',
        'data_inicio',
        'data_fim',
        'proxima_execucao',
        'ativo',
        'ai_model_id',
    ];

    protected $casts = [
        'interpretar' => 'boolean',
        'ativo' => 'boolean',
        'dia_semana' => 'integer',
        'intervalo_minutos' => 'integer',
        'dias_semana' => 'array',
        'data_inicio' => 'date',
        'data_fim' => 'date',
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

    public function aiModel()
    {
        return $this->belongsTo(AiModel::class);
    }

    /**
     * Modelo de IA efetivo: o escolhido no agendamento ou, se nenhum,
     * o modelo de texto padrão configurado no portal (Admin).
     */
    public function aiModelEfetivo(): ?AiModel
    {
        return $this->aiModel ?? (new AiProviderService)->getTextToTextProvider();
    }

    public function conteudosDinamicos()
    {
        return $this->belongsToMany(
            ConteudoDinamico::class,
            'agendamento_conteudo_dinamico',
            'agendamento_id',
            'conteudo_dinamico_id'
        );
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
            if ($this->data_fim && $this->proxima_execucao && $this->proxima_execucao->gt($this->data_fim->copy()->endOfDay())) {
                return null;
            }

            return $this->proxima_execucao;
        }

        // Respeita a data de início do período.
        if ($this->data_inicio && $base->lt($this->data_inicio)) {
            $base = $this->data_inicio->copy()->startOfDay();
        }

        $hora = $this->hora ?? '09:00';

        if ($this->frequencia === 'diario') {
            $dias = collect($this->dias_semana ?? [])->map(fn ($d) => (int) $d)->all();
            $next = $base->copy()->setTimeFromTimeString($hora);
            $tentativas = 0;

            while ($tentativas < 14) {
                if ($next->gt($base) && (empty($dias) || in_array($next->dayOfWeek, $dias, true))) {
                    break;
                }
                $next->addDay()->setTimeFromTimeString($hora);
                $tentativas++;
            }

            if ($this->data_fim && $next->gt($this->data_fim->copy()->endOfDay())) {
                return null;
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

            if ($this->data_fim && $next->gt($this->data_fim->copy()->endOfDay())) {
                return null;
            }

            return $next;
        }

        if ($this->frequencia === 'intervalo') {
            $next = $this->proximoSlotIntervalo($base);

            if ($next && $this->data_fim && $next->gt($this->data_fim->copy()->endOfDay())) {
                return null;
            }

            return $next;
        }

        return null;
    }

    /**
     * Próximo slot de um agendamento por intervalo (a cada N minutos
     * entre hora_inicio e hora_fim, apenas nos dias permitidos).
     */
    private function proximoSlotIntervalo(\Carbon\Carbon $base): ?\Carbon\Carbon
    {
        $intervalo = (int) $this->intervalo_minutos ?: 60;
        $inicio = $this->hora_inicio ?? '00:00';
        $fim = $this->hora_fim ?? '23:59';
        $dias = collect($this->dias_semana ?? [])->map(fn ($d) => (int) $d)->all();

        for ($d = 0; $d < 8; $d++) {
            $dia = $base->copy()->startOfDay()->addDays($d);

            if (! empty($dias) && ! in_array($dia->dayOfWeek, $dias, true)) {
                continue;
            }

            $inicioHoje = $dia->copy()->setTimeFromTimeString($inicio);
            $fimHoje = $dia->copy()->setTimeFromTimeString($fim);

            if ($fimHoje->lt($base)) {
                continue;
            }

            if ($inicioHoje->gte($base)) {
                $candidate = $inicioHoje;
            } else {
                $diffMin = (int) $inicioHoje->diffInMinutes($base);
                $n = (int) ceil($diffMin / $intervalo);
                $candidate = $inicioHoje->copy()->addMinutes($n * $intervalo);
            }

            if ($candidate->lte($base)) {
                $candidate->addMinutes($intervalo);
            }

            if ($candidate->lte($fimHoje)) {
                return $candidate;
            }
        }

        return null;
    }

    public function getFrequenciaLabelAttribute(): string
    {
        $periodo = $this->periodoLabel();

        return match ($this->frequencia) {
            'una_vez' => 'Uma vez'.$periodo,
            'diario' => 'Diário ('.$this->diaSemanaLabel().') às '.($this->hora ?? '09:00').$periodo,
            'semanal' => 'Semanal ('.$this->diaSemanaLabel().') às '.($this->hora ?? '09:00').$periodo,
            'intervalo' => 'A cada '.($this->intervalo_minutos ?? 60).' min ('.($this->hora_inicio ?? '00:00').'–'.($this->hora_fim ?? '23:59').') · '.$this->diaSemanaLabel().$periodo,
            default => (string) $this->frequencia,
        };
    }

    public function periodoLabel(): string
    {
        if (! $this->data_inicio && ! $this->data_fim) {
            return '';
        }

        $ini = $this->data_inicio?->format('d/m/Y');
        $fim = $this->data_fim?->format('d/m/Y');

        if ($ini && $fim) {
            return " (de {$ini} a {$fim})";
        }

        if ($ini) {
            return " (a partir de {$ini})";
        }

        return " (até {$fim})";
    }

    private function diaSemanaLabel(): string
    {
        if ($this->frequencia === 'semanal') {
            $dias = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

            return $dias[$this->dia_semana] ?? '—';
        }

        $permitidos = collect($this->dias_semana ?? [])->map(fn ($d) => (int) $d)->all();

        if (empty($permitidos)) {
            return 'todos os dias';
        }

        if (sort($permitidos) && $permitidos === [1, 2, 3, 4, 5]) {
            return 'dias úteis';
        }

        $nomes = [0 => 'Dom', 1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb'];

        return collect($permitidos)->sort()->map(fn ($d) => $nomes[$d] ?? '?')->implode(', ');
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

    /**
     * Atualiza o agendamento após um envio bem-sucedido, recalculando a
     * próxima execução (ou desativando, no caso de envio único).
     */
    public function atualizarAposEnvio(): void
    {
        if ($this->frequencia === 'una_vez') {
            $this->ativo = false;
            $this->proxima_execucao = null;
        } else {
            $proxima = $this->calcularProximaExecucao(now());

            if ($proxima === null) {
                $this->ativo = false;
                $this->proxima_execucao = null;
            } else {
                $this->proxima_execucao = $proxima;
            }
        }

        $this->save();
    }
}
