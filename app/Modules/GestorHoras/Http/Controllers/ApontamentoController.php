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
        if (! empty($validated['gh_contrato_item_id'])) {
            $itemPertence = $contrato->itens()->where('id', $validated['gh_contrato_item_id'])->exists();
            if (! $itemPertence) {
                return back()->withErrors(['gh_contrato_item_id' => 'O item selecionado não pertence a este contrato.']);
            }

            // Impede lançamentos em itens homologados
            $item = $contrato->itens()->where('id', $validated['gh_contrato_item_id'])->first();
            if ($item && (! empty($item->homologado))) {
                return back()->withErrors(['erro' => 'Este item já foi homologado e não permite novos lançamentos.']);
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

        $apontamentosAgrupados = $apontamentos->groupBy(fn ($a) => $a->data_realizacao->format('Y-m-d'));

        foreach ($apontamentosAgrupados as $dataAgrupada => $itensDoDia) {
            foreach ($itensDoDia as $index => $apontamento) {
                $horaInicio = $apontamento->iniciado_em?->format('H:i');
                $horaFim = $apontamento->finalizado_em?->format('H:i');

                if (! $horaInicio && $horaFim && $apontamento->minutos_gastos > 0) {
                    $horaInicio = $apontamento->finalizado_em
                        ->copy()
                        ->subMinutes((int) $apontamento->minutos_gastos)
                        ->format('H:i');
                }

                $horaInicioTexto = $horaInicio ?? 'não informado';
                $horaFimTexto = $horaFim ?? 'não informado';
                $horasFormatadas = number_format($apontamento->horas, 2, ',', '.');

                $descricoes = array_filter(
                    array_map('trim', explode("\n", $apontamento->descricao))
                );

                $primeiraLinha = true;
                foreach ($descricoes as $descricao) {
                    if ($primeiraLinha) {
                        if ($index === 0) {
                            $prefixoData = $apontamento->data_realizacao->format('d/m/Y');
                            $linhas[] = "- {$prefixoData} | {$descricao} | {$horasFormatadas} h";
                        } else {
                            $espacos = str_repeat(' ', 12);
                            $linhas[] = "{$espacos}| {$descricao} | {$horasFormatadas} h";
                        }
                        $primeiraLinha = false;
                    } else {
                        $espacos = str_repeat(' ', 12);
                        $linhas[] = "{$espacos}| {$descricao}";
                    }
                }

                $totalMinutos += (int) $apontamento->minutos_gastos;
            }
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
            'Fico à disposição para qualquer ajuste ou esclarecimento.';
    }

    public function exportExcelSeparados(Request $request, $contrato_id)
    {
        Gate::authorize('gh.operacional');

        $contrato = Contrato::findOrFail($contrato_id);

        if ($contrato->tipo !== 'livre') {
            return back()->withErrors(['erro' => 'Esta ação está disponível apenas para contratos livres.']);
        }

        $apontamentos = $contrato->apontamentos()
            ->where('faturamento_status', 'separado')
            ->orderBy('data_realizacao', 'asc')
            ->get();

        if ($apontamentos->isEmpty()) {
            return back()->withErrors(['erro' => 'Não há apontamentos separados para exportar.']);
        }

        $rows = [];
        $rows[] = ['Data', 'Descrição', 'Tempo (h)', 'Status'];

        foreach ($apontamentos as $a) {
            // Normaliza quebras: trata sequências literais "\\r\\n" (backslash + r + backslash + n)
            // e quebras reais CRLF/CR para LF, produzindo newline real para o XML do Excel.
            $descricao = (string) $a->descricao;
            $descricao = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $descricao); // literais
            $descricao = str_replace(["\r\n", "\r"], "\n", $descricao); // reais

            $rows[] = [
                $a->data_realizacao->format('d/m/Y'),
                $descricao,
                number_format($a->horas, 2, ',', '.'),
                ucfirst(str_replace('_', ' ', $a->faturamento_status)),
            ];
        }

        $sheetXml = '<?xml version="1.0" encoding="UTF-8"?>';
        $sheetXml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
        $sheetXml .= '<sheetData>';
        $r = 1;
        foreach ($rows as $row) {
            $sheetXml .= '<row r="'.$r.'">';
            $c = 0;
            foreach ($row as $cell) {
                $c++;
                $col = $this->numToCol($c);
                $cellEscaped = htmlspecialchars((string) $cell, ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8');
                // Preserva quebras de linha no Excel
                $sheetXml .= '<c r="'.$col.$r.'" t="inlineStr"><is><t xml:space="preserve">'.$cellEscaped.'</t></is></c>';
            }
            $sheetXml .= '</row>';
            $r++;
        }
        $sheetXml .= '</sheetData></worksheet>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
            '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
            '<Default Extension="xml" ContentType="application/xml"/>' .
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
            '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
            '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
            '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
            '</Relationships>';

        $workbook = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
            '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>' .
            '</workbook>';

        $workbookRels = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
            '</Relationships>';

        $styles = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
            '<fonts count="1"><font><sz val="11"/></font></fonts>' .
            '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>' .
            '<borders count="1"><border/></borders>' .
            '<cellStyleXfs count="1"><xf/></cellStyleXfs>' .
            '<cellXfs count="1"><xf/></cellXfs>' .
            '</styleSheet>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'gh_xlsx_');
        $zip = new \ZipArchive();
        if ($zip->open($tmpFile, \ZipArchive::OVERWRITE) !== true) {
            return back()->withErrors(['erro' => 'Não foi possível criar arquivo temporário para exportação.']);
        }

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('xl/styles.xml', $styles);
        $zip->close();

        $filename = 'contrato-'.$contrato->id.'-apontamentos-separados.xlsx';

        return response()->download($tmpFile, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    private function numToCol(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $mod = ($n - 1) % 26;
            $s = chr(65 + $mod) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
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

        if (! $itemPertence) {
            return back()->withErrors(['gh_contrato_item_id' => 'O item selecionado não pertence ao contrato informado.']);
        }

        $item = $contrato->itens()->where('id', $validated['gh_contrato_item_id'])->first();
        if ($item && $item->homologado) {
            return back()->withErrors(['erro' => 'Não é possível iniciar apontamento em item já homologado.']);
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

        if (! $apontamento) {
            return back()->withErrors(['erro' => 'Nenhum apontamento ativo foi encontrado para finalizar.']);
        }

        // Se o apontamento pertence a um item homologado, bloqueia alteração
        if ($apontamento->gh_contrato_item_id) {
            $item = $apontamento->item()->first();
            if ($item && $item->homologado) {
                return back()->withErrors(['erro' => 'Este apontamento pertence a um item homologado e não pode ser alterado.']);
            }
        }

        $inicio = $apontamento->iniciado_em ?? $apontamento->created_at;
        $fim = now();
        $minutos = max(1, (int) $inicio->diffInMinutes($fim));

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

        if (! $apontamento) {
            return response()->json(['erro' => 'Nenhum apontamento ativo encontrado.'], 422);
        }

        $apontamento->update([
            'descricao' => $validated['descricao'] ?? '',
        ]);

        return response()->json(['success' => true, 'mensagem' => 'Descrição salva com sucesso!']);
    }
}
