<?php

namespace App\Modules\Mithril\Services\Pidgey;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Mithril\Services\ContasPendentesService;
use App\Modules\Pidgey\Contracts\ProvedorConteudo;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Services\VozPersona;
use Illuminate\Support\Collection;

/**
 * Aviso de contas a pagar (atrasadas, de hoje e dos próximos dias) como conteúdo dinâmico do Pidgey. Os
 * valores e datas são determinísticos; a persona só escreve a abertura. Sem pendências, não envia nada.
 */
class ContasAPagarProvedor implements ProvedorConteudo
{
    public const TIPO = 'contas_a_pagar';

    public const INSTRUCAO = 'Os detalhes das contas (descrições, valores e datas) serão ACRESCENTADOS AUTOMATICAMENTE logo abaixo do seu texto: NÃO os repita. Comente só o essencial no tom da persona: cobre com firmeza as atrasadas, avise das que vencem hoje e, se não há nada atrasado, seja leve. Pode citar o nome de uma conta, exatamente como está nos fatos.';

    private const ROTULOS = [
        'atrasadas' => '🔴 <b>Atrasadas</b>',
        'hoje' => '🟠 <b>Vencem hoje</b>',
        'proximas' => '🟡 <b>Nos próximos dias</b>',
    ];

    public function __construct(
        private readonly ContasPendentesService $contas,
        private readonly VozPersona $voz,
    ) {}

    public function tipo(): string
    {
        return self::TIPO;
    }

    public function mensagem(Agendamento $agendamento, Persona $persona): ?string
    {
        $grupos = $this->contas->para((int) $agendamento->user_id);

        if (collect($grupos)->every(fn (Collection $g): bool => $g->isEmpty())) {
            return null;
        }

        $abertura = $this->voz->abrir($persona, $this->fatos($grupos), self::INSTRUCAO)
            ?? e('Atenção às contas para pagar.');

        return $abertura."\n\n".$this->bloco($grupos);
    }

    /**
     * Bloco HTML com as contas, ou '' quando não há nenhuma.
     *
     * @param  array{atrasadas: Collection, hoje: Collection, proximas: Collection}  $grupos
     */
    public function bloco(array $grupos): string
    {
        $blocos = [];

        foreach (self::ROTULOS as $chave => $rotulo) {
            if ($grupos[$chave]->isEmpty()) {
                continue;
            }

            $linhas = $grupos[$chave]->map(fn (array $c): string => '• '.e($c['descricao']).' — '.$this->dinheiro($c['valor']).' ('.$c['vencimento']->format('d/m').')');
            $blocos[] = $rotulo."\n".$linhas->implode("\n");
        }

        return $blocos === [] ? '' : "💳 <b>Contas a pagar</b>\n\n".implode("\n\n", $blocos);
    }

    /**
     * Fatos em texto puro para a persona comentar.
     *
     * @param  array{atrasadas: Collection, hoje: Collection, proximas: Collection}  $grupos
     */
    public function fatos(array $grupos): string
    {
        $nomes = ['atrasadas' => 'Atrasadas', 'hoje' => 'Vencem hoje', 'proximas' => 'Vencem nos próximos dias'];
        $linhas = [];

        foreach ($nomes as $chave => $nome) {
            $linhas[] = $grupos[$chave]->isEmpty()
                ? "{$nome}: nenhuma."
                : "{$nome}: ".$grupos[$chave]->map(fn (array $c): string => $c['descricao'].' ('.$this->dinheiro($c['valor']).', dia '.$c['vencimento']->format('d/m').')')->implode('; ').'.';
        }

        return implode("\n", $linhas);
    }

    private function dinheiro(float $valor): string
    {
        return 'R$ '.number_format($valor, 2, ',', '.');
    }
}
