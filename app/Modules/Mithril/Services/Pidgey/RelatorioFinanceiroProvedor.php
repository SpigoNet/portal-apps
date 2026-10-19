<?php

namespace App\Modules\Mithril\Services\Pidgey;

use App\Modules\Alfred\Models\Persona;
use App\Modules\Mithril\Services\ContasPendentesService;
use App\Modules\Pidgey\Contracts\ProvedorConteudo;
use App\Modules\Pidgey\Models\Agendamento;
use App\Modules\Pidgey\Services\InterpretadorPersonaService;
use App\Modules\Pidgey\Services\ResumoFinanceiroService;

/**
 * Relatório financeiro do dia (Nami): a persona resume o relatório do Mithril (com lógica de "no vermelho") e,
 * abaixo, vão as contas a pagar pendentes. Sem relatório e sem contas, não envia nada.
 */
class RelatorioFinanceiroProvedor implements ProvedorConteudo
{
    public const TIPO = 'relatorio_financeiro';

    public function __construct(
        private readonly ResumoFinanceiroService $resumo,
        private readonly InterpretadorPersonaService $interpretador,
        private readonly ContasPendentesService $contas,
        private readonly ContasAPagarProvedor $contasProvedor,
    ) {}

    public function tipo(): string
    {
        return self::TIPO;
    }

    public function mensagem(Agendamento $agendamento, Persona $persona): ?string
    {
        $relatorio = $this->resumo->obterRelatorio((int) now()->month, (int) now()->year);
        $bloco = $this->contasProvedor->bloco($this->contas->para((int) $agendamento->user_id));

        if ($relatorio === '' && $bloco === '') {
            return null;
        }

        $resumo = '';

        if ($relatorio !== '') {
            $saldo = $this->resumo->saldoEfetivado($relatorio);
            $texto = $this->interpretador->resumirFinanceiro($persona, $relatorio, $saldo !== null && $saldo < 0, $agendamento->aiModelEfetivo());

            // Se a IA falhar, o serviço devolve o próprio relatório: não vale enviar o markdown cru.
            $resumo = $texto === $relatorio ? '' : e($texto);
        }

        if ($resumo === '') {
            $resumo = e('Resumo financeiro de hoje.');
        }

        return $bloco === '' ? $resumo : $resumo."\n\n".$bloco;
    }
}
