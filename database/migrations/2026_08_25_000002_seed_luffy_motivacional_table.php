<?php

use App\Models\User;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Models\ConteudoDinamico;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $conteudo = ConteudoDinamico::create([
            'nome' => 'Mensagem Motivacional (Luffy)',
            'tipo' => 'texto',
            'conteudo' => 'Bom dia, capitão! Acorda com tudo hoje, porque sonho não se conquista dormindo! '.
                'Cada tarefa é um novo adventure: encare como uma batalha épica, dê risada dos obstáculos e siga em frente. '.
                'Se o dia ficar difícil, relaxa — pirata de verdade nunca desiste! Bora conquistar hoje, eu tô contigo! 🏴‍☠️',
            'ativo' => true,
        ]);

        // Agendamento diário para o usuário dono (Gustavo, id 1), interpretado pelo Luffy.
        if (User::where('id', 1)->exists()) {
            $ag = new Agendamento;
            $ag->user_id = 1;
            $ag->persona_slug = 'luffy';
            $ag->canal = 'telegram';
            $ag->mensagem = '';
            $ag->interpretar = true;
            $ag->frequencia = 'diario';
            $ag->hora = '09:00';
            $ag->dias_semana = null;
            $ag->ativo = true;
            $ag->proxima_execucao = $ag->calcularProximaExecucao(now());
            $ag->save();

            $ag->conteudosDinamicos()->attach($conteudo->id);
        }
    }

    public function down(): void
    {
        ConteudoDinamico::query()->where('nome', 'Mensagem Motivacional (Luffy)')->delete();
        Agendamento::query()
            ->where('user_id', 1)
            ->where('persona_slug', 'luffy')
            ->where('frequencia', 'diario')
            ->where('mensagem', '')
            ->delete();
    }
};
