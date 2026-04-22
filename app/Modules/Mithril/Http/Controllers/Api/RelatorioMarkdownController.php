<?php

namespace App\Modules\Mithril\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Mithril\Models\Conta;
use App\Modules\Mithril\Models\PreTransacao;
use App\Modules\Mithril\Models\SaldoFechamento;
use App\Modules\Mithril\Models\Transacao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RelatorioMarkdownController extends Controller
{
    public function index(Request $request): Response
    {
        $mes = $request->input('mes', now()->month);
        $ano = $request->input('ano', now()->year);
        $contaId = $request->input('conta_id');

        $dataBase = Carbon::createFromDate($ano, $mes, 1);
        $inicioMes = $dataBase->copy()->startOfMonth();
        $fimMes = $dataBase->copy()->endOfMonth();
        $dataMesAnterior = $dataBase->copy()->subMonth();

        $contas = Conta::where('tipo', 'normal');
        if ($contaId) {
            $contas->where('id', $contaId);
        }
        $contas = $contas->get();

        $saldoInicial = 0;
        foreach ($contas as $c) {
            $fechamento = SaldoFechamento::where('conta_id', $c->id)
                ->where('ano', $dataMesAnterior->year)
                ->where('mes', $dataMesAnterior->month)
                ->first();

            $saldoInicial += $fechamento ? $fechamento->saldo_final : $c->saldo_inicial;
        }

        $transacoes = Transacao::with('conta')
            ->whereBetween('data_efetiva', [$inicioMes, $fimMes]);
        if ($contaId) {
            $transacoes->where('conta_id', $contaId);
        }
        $transacoes = $transacoes->orderBy('data_efetiva')->get();

        $preTransacoesQuery = PreTransacao::with('conta')->where('ativa', true);
        if ($contaId) {
            $preTransacoesQuery->where('conta_id', $contaId);
        }
        $preTransacoesTodas = $preTransacoesQuery->get();
        $lancamentosProjetados = collect();

        $idsJaEfetivados = $transacoes->pluck('pre_transacao_id')->filter()->toArray();

        foreach ($preTransacoesTodas as $pt) {
            if (in_array($pt->id, $idsJaEfetivados)) {
                continue;
            }

            $parcelaAtualCalculada = null;

            if ($pt->tipo === 'recorrente') {
                if ($pt->data_inicio->startOfMonth()->gt($fimMes)) {
                    continue;
                }
            } elseif ($pt->tipo === 'parcelada') {
                $diffMeses = $this->diffEmMeses($pt->data_inicio, $dataBase);
                if ($diffMeses < 0 || $diffMeses >= $pt->total_parcelas) {
                    continue;
                }
                $parcelaAtualCalculada = $diffMeses + 1;
            }

            $dataPrevista = Carbon::create($ano, $mes, $pt->dia_vencimento);
            if ($dataPrevista->month != $mes) {
                $dataPrevista = Carbon::create($ano, $mes, 1)->endOfMonth();
            }

            $status = $this->getStatus($pt, $dataBase);

            $lancamentosProjetados->push([
                'id' => null,
                'pre_transacao_id' => $pt->id,
                'conta_id' => $pt->conta_id,
                'data_efetiva' => $dataPrevista->format('Y-m-d'),
                'descricao' => $pt->descricao,
                'conta_nome' => $pt->conta?->nome,
                'valor' => (float) $pt->valor_parcela,
                'status' => $status,
                'tipo' => 'projetado',
                'meta_parcela' => $parcelaAtualCalculada ? "{$parcelaAtualCalculada}/{$pt->total_parcelas}" : null,
            ]);
        }

        $lancamentosReais = $transacoes->map(fn ($t) => [
            'id' => $t->id,
            'pre_transacao_id' => $t->pre_transacao_id,
            'conta_id' => $t->conta_id,
            'data_efetiva' => $t->data_efetiva->format('Y-m-d'),
            'descricao' => $t->descricao,
            'conta_nome' => $t->conta?->nome,
            'valor' => (float) $t->valor,
            'status' => 'efetivado',
            'tipo' => 'real',
            'meta_parcela' => null,
        ]);

        $listaCombinada = $lancamentosReais->merge($lancamentosProjetados)->sortBy('data_efetiva')->values();

        $acumuladoEfetivado = $saldoInicial;
        $acumuladoPrevisto = $saldoInicial;

        foreach ($listaCombinada as &$item) {
            $acumuladoPrevisto += $item['valor'];

            if ($item['status'] === 'efetivado') {
                $acumuladoEfetivado += $item['valor'];
            }

            $item['saldo_acumulado_efetivado'] = $acumuladoEfetivado;
            $item['saldo_acumulado_previsto'] = $acumuladoPrevisto;
        }

        $totalEntradas = $listaCombinada->filter(fn ($l) => $l['valor'] > 0)->sum('valor');
        $totalSaidas = $listaCombinada->filter(fn ($l) => $l['valor'] < 0)->sum('valor');
        $qtdEfetivados = $listaCombinada->filter(fn ($l) => $l['status'] === 'efetivado')->count();
        $qtdAgendados = $listaCombinada->filter(fn ($l) => $l['status'] === 'agendado')->count();
        $qtdConfirmados = $listaCombinada->filter(fn ($l) => $l['status'] === 'confirmado')->count();

        $nomeMes = $dataBase->translatedFormat('F');
        $nomeMes = ucfirst($nomeMes);

        $markdown = $this->gerarMarkdown([
            'mes' => $mes,
            'ano' => $ano,
            'nome_mes' => $nomeMes,
            'saldo_inicial' => $saldoInicial,
            'total_entradas' => $totalEntradas,
            'total_saidas' => $totalSaidas,
            'saldo_acumulado_efetivado' => $acumuladoEfetivado,
            'saldo_acumulado_previsto' => $acumuladoPrevisto,
            'qtd_efetivados' => $qtdEfetivados,
            'qtd_agendados' => $qtdAgendados,
            'qtd_confirmados' => $qtdConfirmados,
            'lancamentos' => $listaCombinada,
            'contas' => $contas,
        ]);

        return response($markdown, 200, [
            'Content-Type' => 'text/markdown; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"relatorio-{$ano}-{$mes}.md\"",
        ]);
    }

    private function gerarMarkdown(array $dados): string
    {
        $md = "# Relatório de Lançamentos\n\n";
        $md .= "**Período:** {$dados['nome_mes']} de {$dados['ano']}\n\n";
        $md .= "---\n\n";

        // Seção de referência técnica de contas
        $md .= "## Referência de Contas\n\n";
        $md .= "| conta_id | Nome | Tipo |\n";
        $md .= "|----------|------|------|\n";
        foreach ($dados['contas'] as $conta) {
            $md .= "| {$conta->id} | {$conta->nome} | {$conta->tipo} |\n";
        }
        $md .= "\n---\n\n";

        $md .= "## Resumo Financeiro\n\n";
        $md .= "| Indicador | Valor |\n";
        $md .= "|-----------|-------|\n";
        $md .= sprintf("| Saldo Inicial | R$ %s |\n", number_format($dados['saldo_inicial'], 2, ',', '.'));
        $md .= sprintf("| Total Entradas | R$ %s |\n", number_format($dados['total_entradas'], 2, ',', '.'));
        $md .= sprintf("| Total Saídas | R$ %s |\n", number_format($dados['total_saidas'], 2, ',', '.'));
        $md .= sprintf("| Saldo Acumulado (Efetivado) | R$ %s |\n", number_format($dados['saldo_acumulado_efetivado'], 2, ',', '.'));
        $md .= sprintf("| Saldo Acumulado (Previsto) | R$ %s |\n\n", number_format($dados['saldo_acumulado_previsto'], 2, ',', '.'));

        $md .= "## Quantidade por Status\n\n";
        $md .= "| Status | Quantidade |\n";
        $md .= "|--------|------------|\n";
        $md .= sprintf("| Efetivado | %d |\n", $dados['qtd_efetivados']);
        $md .= sprintf("| Confirmado | %d |\n", $dados['qtd_confirmados']);
        $md .= sprintf("| Agendado | %d |\n\n", $dados['qtd_agendados']);

        $md .= "## Lançamentos\n\n";
        $md .= '> **IDs para uso na API:** `transacao_id` identifica registros em `/api/mithril/transacoes/{id}`; ';
        $md .= '`pre_transacao_id` identifica registros em `/api/mithril/pre-transacoes/{id}`; ';
        $md .= "`conta_id` identifica registros em `/api/mithril/contas/{id}`.\n\n";

        if ($dados['lancamentos']->isEmpty()) {
            $md .= "*Nenhum lançamento encontrado para este período.*\n";
        } else {
            $md .= "| transacao_id | pre_transacao_id | conta_id | Data | Descrição | Conta | Parcela | Valor | Status | Saldo Efetivado | Saldo Previsto |\n";
            $md .= "|-------------|-----------------|---------|------|-----------|-------|---------|-------|--------|-----------------|----------------|\n";

            foreach ($dados['lancamentos'] as $l) {
                $transacaoId = $l['id'] !== null ? $l['id'] : '-';
                $preTransacaoId = $l['pre_transacao_id'] !== null ? $l['pre_transacao_id'] : '-';
                $contaId = $l['conta_id'] !== null ? $l['conta_id'] : '-';
                $data = Carbon::parse($l['data_efetiva'])->format('d/m');
                $descricao = $l['descricao'];
                $conta = $l['conta_nome'] ?? '-';
                $parcela = $l['meta_parcela'] ?? '-';
                $valor = ($l['valor'] >= 0 ? '+' : '').'R$ '.number_format($l['valor'], 2, ',', '.');
                $status = $l['status'];
                $saldoEfetivado = number_format($l['saldo_acumulado_efetivado'] ?? 0, 2, ',', '.');
                $saldoPrevisto = number_format($l['saldo_acumulado_previsto'] ?? 0, 2, ',', '.');

                $md .= "| {$transacaoId} | {$preTransacaoId} | {$contaId} | {$data} | {$descricao} | {$conta} | {$parcela} | {$valor} | {$status} | R$ {$saldoEfetivado} | R$ {$saldoPrevisto} |\n";
            }
        }

        $md .= "\n---\n\n";
        $md .= "*Relatório gerado automaticamente pelo módulo Mithril.*\n";

        return $md;
    }

    private function diffEmMeses($dataInicio, $dataAtual): int
    {
        $inicio = Carbon::parse($dataInicio)->startOfMonth();
        $atual = Carbon::parse($dataAtual)->startOfMonth();

        return $inicio->diffInMonths($atual, false);
    }

    private function getStatus($pt, $dataMesAtual): string
    {
        if ($pt->data_ultima_acao) {
            $ultimaAcao = Carbon::parse($pt->data_ultima_acao);
            if ($ultimaAcao->format('Y-m') === $dataMesAtual->format('Y-m')) {
                return 'confirmado';
            }
        }

        return 'agendado';
    }
}
