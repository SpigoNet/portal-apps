<?php

namespace App\Modules\Alfred\Services\Pidgey;

use App\Models\User;
use App\Modules\Alfred\Models\ConsumoAgua;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Alfred\Models\Rotina;
use App\Modules\Alfred\Models\UserProfile;
use App\Modules\Pidgey\Contracts\ProvedorConteudo;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Services\VozPersona;
use App\Modules\TreeTask\Models\Tarefa;
use App\Modules\TreeTask\Services\TarefasDoUsuarioService;

/**
 * Motivação do dia (Luffy) que leva em conta o dia de quem recebe: tarefas, energia, "dia ruim", água de ontem e
 * rotinas. A persona escreve só a fala; se a IA falhar, vai uma frase fixa.
 */
class ContextoDiaProvedor implements ProvedorConteudo
{
    public const TIPO = 'contexto_dia';

    private const FRASE_FIXA = 'Bom dia, capitão! Bora conquistar hoje, eu tô contigo! 🏴‍☠️';

    private const INSTRUCAO = 'Escreva uma mensagem motivacional de bom dia, no tom da persona, personalizada com os fatos do dia (cite no máximo uma ou duas tarefas, pelo título exatamente como está nos fatos). Se o dia está marcado como ruim ou a energia é baixa, seja acolhedor e leve, sem cobrança e sem sobrecarregar. Se a energia é alta e o dia está leve, seja empolgado. Se há tarefas atrasadas, incentive sem culpar. Nunca invente tarefas, rotinas ou números.';

    public function __construct(
        private readonly TarefasDoUsuarioService $tarefas,
        private readonly VozPersona $voz,
    ) {}

    public function tipo(): string
    {
        return self::TIPO;
    }

    public function mensagem(Agendamento $agendamento, Persona $persona): ?string
    {
        $userId = (int) $agendamento->user_id;
        $fala = $this->voz->abrir($persona, $this->fatos($userId), self::INSTRUCAO);

        return $fala ?? e(trim((string) $agendamento->mensagem) !== '' ? $agendamento->mensagem : self::FRASE_FIXA);
    }

    private function fatos(int $userId): string
    {
        $perfil = UserProfile::query()->where('user_id', $userId)->first();
        $hoje = $this->tarefas->hoje($userId);
        $atrasadas = $this->tarefas->atrasadas($userId);
        $rotinas = Rotina::query()->doUsuario($userId)->paraHoje();

        $linhas = [
            'Dia marcado como ruim pelo usuário: '.($perfil?->modo_dia_ruim ? 'SIM' : 'não').'.',
            'Energia atual: '.($perfil?->energia_atual ?? 'media').'.',
            'Tarefas que vencem hoje: '.$this->titulos($hoje->all()).'.',
            'Tarefas atrasadas: '.($atrasadas->isEmpty() ? 'nenhuma' : $atrasadas->count().' ('.$this->titulos($atrasadas->take(3)->all()).')').'.',
            'Rotinas de hoje: '.($rotinas->isEmpty() ? 'nenhuma' : $rotinas->pluck('titulo')->take(5)->implode(', ')).'.',
        ];

        $ontem = $this->aguaDeOntem($userId);
        if ($ontem !== null) {
            $linhas[] = $ontem;
        }

        return implode("\n", $linhas);
    }

    /**
     * @param  array<int, Tarefa>  $tarefas
     */
    private function titulos(array $tarefas): string
    {
        return $tarefas === [] ? 'nenhuma' : collect($tarefas)->pluck('titulo')->take(5)->implode('; ');
    }

    private function aguaDeOntem(int $userId): ?string
    {
        $meta = User::find($userId)?->getMetaAguaMl() ?? 2500;
        $consumido = (int) ConsumoAgua::query()->doUsuario($userId)->whereDate('data', now()->subDay()->toDateString())->sum('quantidade_ml');

        if ($consumido === 0) {
            return null;
        }

        return 'Água ontem: '.$consumido.' ml de uma meta de '.$meta.' ml ('.($consumido >= $meta ? 'meta batida' : 'meta não atingida').').';
    }
}
