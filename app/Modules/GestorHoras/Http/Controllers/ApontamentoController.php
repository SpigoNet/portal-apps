<?php

namespace App\Modules\GestorHoras\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestorHoras\Models\Apontamento;
use App\Modules\GestorHoras\Models\Contrato;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ApontamentoController extends Controller
{
    /**
     * Salva um novo apontamento de horas.
     */
    public function store(Request $request, $contrato_id)
    {
        Gate::authorize('gh.operacional');

        $contrato = Contrato::findOrFail($contrato_id);
        // --- TRAVA DE SEGURANÇA ---
        if ($contrato->status !== 'ativo') {
            return back()->withErrors(['erro' => 'Não é possível lançar horas em um contrato finalizado ou cancelado.']);
        }

        $validated = $request->validate([
            'descricao' => 'required|string',
            'data_realizacao' => 'required|date',
            'horas_gastas' => 'required|numeric|min:0.1',
            'gh_contrato_item_id' => 'nullable|exists:gh_contrato_itens,id',
        ]);

        // Verifica se o item pertence mesmo a este contrato (segurança extra)
        if (!empty($validated['gh_contrato_item_id'])) {
            $itemPertence = $contrato->itens()->where('id', $validated['gh_contrato_item_id'])->exists();
            if (!$itemPertence) {
                return back()->withErrors(['gh_contrato_item_id' => 'O item selecionado não pertence a este contrato.']);
            }
        }

        $minutos = $validated['horas_gastas'] * 60;

        $contrato->apontamentos()->create([
            'gh_contrato_item_id' => $validated['gh_contrato_item_id'] ?? null,
            'user_id' => auth()->id(),
            'descricao' => $validated['descricao'],
            'data_realizacao' => $validated['data_realizacao'],
            'minutos_gastos' => $minutos,
            'faturamento_status' => 'nao_separado',
        ]);

        return redirect()->back()->with('success', 'Horas lançadas com sucesso!');
    }

    public function separarParaFaturamento(Request $request, $contrato_id)
    {
        Gate::authorize('gh.operacional');

        $contrato = Contrato::findOrFail($contrato_id);

        if ($contrato->tipo !== 'livre') {
            return back()->withErrors(['erro' => 'A separação para faturamento está disponível apenas para contratos livres.']);
        }

        $validated = $request->validate([
            'apontamentos' => 'required|array|min:1',
            'apontamentos.*' => 'integer|exists:gh_apontamentos,id',
        ]);

        $ids = collect($validated['apontamentos'])->unique()->values();

        $atualizados = $contrato->apontamentos()
            ->whereIn('id', $ids)
            ->where('faturamento_status', 'nao_separado')
            ->update([
                'faturamento_status' => 'separado',
                'faturamento_selecionado_em' => now(),
                'faturamento_selecionado_por' => auth()->id(),
            ]);

        if ($atualizados === 0) {
            return back()->withErrors(['erro' => 'Nenhum apontamento elegível foi atualizado.']);
        }

        return back()->with('success', "{$atualizados} apontamento(s) separado(s) para faturamento.");
    }

    public function atualizarStatusFaturamento(Request $request, $contrato_id)
    {
        Gate::authorize('gh.operacional');

        $contrato = Contrato::findOrFail($contrato_id);

        if ($contrato->tipo !== 'livre') {
            return back()->withErrors(['erro' => 'Esta ação está disponível apenas para contratos livres.']);
        }

        $validated = $request->validate([
            'acao' => 'required|in:separar,aprovar,faturar,gerar_email',
        ]);

        if ($validated['acao'] === 'gerar_email') {
            $apontamentosSeparados = $contrato->apontamentos()
                ->where('faturamento_status', 'separado')
                ->orderBy('data_realizacao', 'asc')
                ->get();

            if ($apontamentosSeparados->isEmpty()) {
                return back()->withErrors([
                    'erro' => 'Não há apontamentos separados para gerar o texto de aprovação.',
                ]);
            }

            return back()
                ->with('success', 'Texto de aprovação gerado com sucesso.')
                ->with('email_aprovacao_texto', $this->montarTextoAprovacaoFaturamento($contrato, $apontamentosSeparados));
        }

        $selecionados = $request->validate([
            'apontamentos' => 'required|array|min:1',
            'apontamentos.*' => 'integer|exists:gh_apontamentos,id',
        ]);

        $transicoes = [
            'separar' => [
                'de' => 'nao_separado',
                'para' => 'separado',
                'mensagem' => 'separado(s) para faturamento',
            ],
            'aprovar' => [
                'de' => 'separado',
                'para' => 'aprovado_cliente',
                'mensagem' => 'marcado(s) como aprovado(s)',
            ],
            'faturar' => [
                'de' => 'aprovado_cliente',
                'para' => 'faturado',
                'mensagem' => 'marcado(s) como faturado(s)',
            ],
        ];

        $transicao = $transicoes[$validated['acao']];
        $ids = collect($selecionados['apontamentos'])->unique()->values();

        $dadosUpdate = [
            'faturamento_status' => $transicao['para'],
        ];

        if ($validated['acao'] === 'separar') {
            $dadosUpdate['faturamento_selecionado_em'] = now();
            $dadosUpdate['faturamento_selecionado_por'] = auth()->id();
        }

        $atualizados = $contrato->apontamentos()
            ->whereIn('id', $ids)
            ->where('faturamento_status', $transicao['de'])
            ->update($dadosUpdate);

        if ($atualizados === 0) {
            return back()->withErrors([
                'erro' => "Nenhum apontamento elegível para a ação '{$validated['acao']}'.",
            ]);
        }

        $response = back()->with('success', "{$atualizados} apontamento(s) {$transicao['mensagem']}.");

        if ($validated['acao'] === 'separar') {
            $apontamentosSeparados = $contrato->apontamentos()
                ->whereIn('id', $ids)
                ->where('faturamento_status', 'separado')
                ->orderBy('data_realizacao', 'asc')
                ->get();

            if ($apontamentosSeparados->isNotEmpty()) {
                $response->with('email_aprovacao_texto', $this->montarTextoAprovacaoFaturamento($contrato, $apontamentosSeparados));
            }
        }

        return $response;
    }

    private function montarTextoAprovacaoFaturamento(Contrato $contrato, $apontamentos): string
    {
        $cliente = $contrato->cliente->nome;
        $linhas = [];
        $totalMinutos = 0;

        foreach ($apontamentos as $apontamento) {
            $horaInicio = $apontamento->iniciado_em?->format('H:i');
            $horaFim = $apontamento->finalizado_em?->format('H:i');

            if (!$horaInicio && $horaFim && $apontamento->minutos_gastos > 0) {
                $horaInicio = $apontamento->finalizado_em
                    ->copy()
                    ->subMinutes((int) $apontamento->minutos_gastos)
                    ->format('H:i');
            }

            $horaInicioTexto = $horaInicio ?? 'não informado';
            $horaFimTexto = $horaFim ?? 'não informado';

            $linhas[] = sprintf(
                '- %s | %s | Início: %s | Fim: %s | %s h',
                $apontamento->data_realizacao->format('d/m/Y'),
                $apontamento->descricao,
                $horaInicioTexto,
                $horaFimTexto,
                number_format($apontamento->horas, 2, ',', '.')
            );
            $totalMinutos += (int) $apontamento->minutos_gastos;
        }

        $totalHoras = number_format($totalMinutos / 60, 2, ',', '.');
        $valorHora = (float) ($contrato->valor_hora ?? 0);
        $valorTotal = number_format(($totalMinutos / 60) * $valorHora, 2, ',', '.');
        $valorHoraFormatado = number_format($valorHora, 2, ',', '.');

        return "Olá, {$cliente}.\n\n".
            "Segue abaixo o resumo das horas separadas para faturamento do contrato '{$contrato->titulo}':\n\n".
            implode("\n", $linhas)."\n\n".
            "Valor hora contratado: R$ {$valorHoraFormatado}\n".
            "Total de horas: {$totalHoras} h\n".
            "Valor total previsto para faturamento: R$ {$valorTotal}\n\n".
            "Por favor, confirme a aprovação deste fechamento para seguirmos com o faturamento.\n\n".
            "Fico à disposição para qualquer ajuste ou esclarecimento.";
    }

    public function mobileTimer()
    {
        Gate::authorize('gh.operacional');

        $apontamentoAtivo = Apontamento::with(['contrato', 'item'])
            ->where('user_id', auth()->id())
            ->where('apontamento_ativo', 1)
            ->first();

        $contratos = Contrato::with(['cliente', 'itens'])
            ->where('status', 'ativo')
            ->orderBy('titulo')
            ->get();

        return view('GestorHoras::mobile.timer', compact('apontamentoAtivo', 'contratos'));
    }

    public function iniciarTimer(Request $request)
    {
        Gate::authorize('gh.operacional');

        $jaAtivo = Apontamento::where('user_id', auth()->id())
            ->where('apontamento_ativo', 1)
            ->exists();

        if ($jaAtivo) {
            return back()->withErrors(['erro' => 'Já existe um apontamento em andamento para este usuário.']);
        }

        $validated = $request->validate([
            'gh_contrato_id' => 'required|exists:gh_contratos,id',
            'gh_contrato_item_id' => 'required|exists:gh_contrato_itens,id',
        ]);

        $contrato = Contrato::where('id', $validated['gh_contrato_id'])
            ->where('status', 'ativo')
            ->firstOrFail();

        $itemPertence = $contrato->itens()
            ->where('id', $validated['gh_contrato_item_id'])
            ->exists();

        if (!$itemPertence) {
            return back()->withErrors(['gh_contrato_item_id' => 'O item selecionado não pertence ao contrato informado.']);
        }

        $contrato->apontamentos()->create([
            'gh_contrato_item_id' => $validated['gh_contrato_item_id'],
            'user_id' => auth()->id(),
            'descricao' => 'Apontamento iniciado via tela mobile.',
            'data_realizacao' => now()->toDateString(),
            'minutos_gastos' => 0,
            'iniciado_em' => now(),
            'apontamento_ativo' => 1,
            'faturamento_status' => 'nao_separado',
        ]);

        return redirect()->route('gestor-horas.mobile.timer')
            ->with('success', 'Apontamento iniciado com sucesso.');
    }

    public function finalizarTimer(Request $request)
    {
        Gate::authorize('gh.operacional');

        $validated = $request->validate([
            'descricao' => 'required|string',
        ]);

        $apontamento = Apontamento::where('user_id', auth()->id())
            ->where('apontamento_ativo', 1)
            ->first();

        if (!$apontamento) {
            return back()->withErrors(['erro' => 'Nenhum apontamento ativo foi encontrado para finalizar.']);
        }

        $inicio = $apontamento->iniciado_em ?? $apontamento->created_at;
        $fim = now();
        $minutos = max(1, $inicio->diffInMinutes($fim));

        $apontamento->update([
            'descricao' => $validated['descricao'],
            'data_realizacao' => $fim->toDateString(),
            'minutos_gastos' => $minutos,
            'finalizado_em' => $fim,
            'apontamento_ativo' => null,
        ]);

        return redirect()->route('gestor-horas.mobile.timer')
            ->with('success', 'Apontamento finalizado com sucesso.');
    }

    public function salvarDescricao(Request $request)
    {
        Gate::authorize('gh.operacional');

        $validated = $request->validate([
            'descricao' => 'nullable|string',
        ]);

        $apontamento = Apontamento::where('user_id', auth()->id())
            ->where('apontamento_ativo', 1)
            ->first();

        if (!$apontamento) {
            return response()->json(['erro' => 'Nenhum apontamento ativo encontrado.'], 422);
        }

        $apontamento->update([
            'descricao' => $validated['descricao'] ?? '',
        ]);

        return response()->json(['success' => true, 'mensagem' => 'Descrição salva com sucesso!']);
    }
}
