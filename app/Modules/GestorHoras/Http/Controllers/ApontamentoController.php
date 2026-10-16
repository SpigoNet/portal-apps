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

        $selecionados = $request->validate([
            'apontamentos' => 'nullable|array',
            'apontamentos.*' => 'integer',
        ])['apontamentos'] ?? [];

        $query = $contrato->apontamentos();

        // Com seleção, exporta exatamente o que foi marcado; sem seleção, mantém o comportamento antigo (separados).
        if (! empty($selecionados)) {
            $query->whereIn('id', $selecionados);
        } else {
            $query->where('faturamento_status', 'separado');
        }

        $apontamentos = $query
            ->orderBy('data_realizacao', 'asc')
            ->orderBy('iniciado_em', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        if ($apontamentos->isEmpty()) {
            return back()->withErrors(['erro' => empty($selecionados)
                ? 'Não há apontamentos separados para exportar.'
                : 'Nenhum dos apontamentos selecionados foi encontrado neste contrato.']);
        }

        $modelo = __DIR__.'/../../resources/templates/apontamento-modelo.xlsx';

        if (! is_file($modelo)) {
            return back()->withErrors(['erro' => 'O modelo de exportação (apontamento-modelo.xlsx) não foi encontrado no servidor.']);
        }

        $tmpFile = tempnam(sys_get_temp_dir(), 'gh_xlsx_');

        if (! copy($modelo, $tmpFile)) {
            return back()->withErrors(['erro' => 'Não foi possível preparar o modelo de exportação.']);
        }

        $zip = new \ZipArchive;
        if ($zip->open($tmpFile) !== true) {
            return back()->withErrors(['erro' => 'Não foi possível abrir o modelo de exportação.']);
        }

        $zip->addFromString('xl/worksheets/sheet1.xml', $this->montarPlanilhaApontamentos($contrato, $apontamentos));
        $zip->addFromString('xl/styles.xml', $this->adicionarEstiloHorasAcumuladas((string) $zip->getFromName('xl/styles.xml')));

        // O calcChain do modelo descreve as células do exemplo; o Excel o recria ao abrir.
        $zip->deleteName('xl/calcChain.xml');
        $zip->addFromString('[Content_Types].xml', str_replace(
            '<Override PartName="/xl/calcChain.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.calcChain+xml"/>',
            '',
            (string) $zip->getFromName('[Content_Types].xml')
        ));
        $zip->addFromString('xl/_rels/workbook.xml.rels', preg_replace(
            '/<Relationship [^>]*calcChain[^>]*\/>/',
            '',
            (string) $zip->getFromName('xl/_rels/workbook.xml.rels')
        ));
        $zip->addFromString('xl/workbook.xml', str_replace(
            '<calcPr calcId="191029"/>',
            '<calcPr calcId="191029" fullCalcOnLoad="1"/>',
            (string) $zip->getFromName('xl/workbook.xml')
        ));
        $zip->close();

        $filename = 'contrato-'.$contrato->id.'-apontamentos-separados.xlsx';

        return response()->download($tmpFile, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    /**
     * Gera o XML da planilha seguindo o modelo (estilos 1 a 13 do styles.xml do modelo).
     */
    private function montarPlanilhaApontamentos(Contrato $contrato, $apontamentos): string
    {
        $valorHora = (float) ($contrato->valor_hora ?? 0);

        // Remove UTF-8 inválido e caracteres de controle, que tornam o XML inválido e truncam a planilha.
        $limpar = fn (string $valor): string => (string) preg_replace(
            '/[^\x{9}\x{A}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            mb_convert_encoding($valor, 'UTF-8', 'UTF-8')
        );
        $texto = fn (string $ref, int $estilo, string $valor): string => '<c r="'.$ref.'" s="'.$estilo.'" t="inlineStr"><is><t xml:space="preserve">'
            .htmlspecialchars($limpar($valor), ENT_XML1 | ENT_SUBSTITUTE, 'UTF-8').'</t></is></c>';
        $numero = fn (string $ref, int $estilo, float $valor): string => '<c r="'.$ref.'" s="'.$estilo.'"><v>'.rtrim(rtrim(number_format($valor, 14, '.', ''), '0'), '.').'</v></c>';

        $cabecalhos = ['Data', 'Dia da Semana', 'H. Início', 'H. Término', 'H. Trabalhadas', 'H. Acumuladas', 'Tipo Atividade', 'Descrição das Atividades', 'Horas Decimais', 'Valor Total'];
        $colunas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

        $linhas = '<row r="1" spans="1:10" ht="15.75">'
            .$texto('I1', 2, 'Valor da Hora')
            .$numero('J1', 1, $valorHora)
            .'</row>';

        $linhas .= '<row r="2" spans="1:10">';
        foreach ($cabecalhos as $i => $titulo) {
            $linhas .= $texto($colunas[$i].'2', 3, $titulo);
        }
        $linhas .= '</row>';

        $linha = 2;
        $acumuladoMinutos = 0;
        $totalValor = 0.0;

        foreach ($apontamentos as $a) {
            $linha++;
            $minutos = (int) $a->minutos_gastos;
            $acumuladoMinutos += $minutos;

            $inicio = $a->iniciado_em;
            $fim = $a->finalizado_em;
            if (! $inicio && $fim && $minutos > 0) {
                $inicio = $fim->copy()->subMinutes($minutos);
            }

            $descricao = (string) $a->descricao;
            $descricao = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $descricao);
            $descricao = str_replace(["\r\n", "\r"], "\n", $descricao);

            $linhasTexto = 0;
            foreach (explode("\n", $descricao) as $parte) {
                $linhasTexto += max(1, (int) ceil(mb_strlen($parte) / 48));
            }
            $altura = max(15, $linhasTexto * 15);

            $horasDecimais = round($minutos / 60, 2);
            $valor = $horasDecimais * $valorHora;
            $totalValor += $valor;

            $serialData = intdiv(gmmktime(0, 0, 0, (int) $a->data_realizacao->format('n'), (int) $a->data_realizacao->format('j'), (int) $a->data_realizacao->format('Y')), 86400) + 25569;
            $fracao = fn ($momento): float => ((int) $momento->format('H') * 3600 + (int) $momento->format('i') * 60 + (int) $momento->format('s')) / 86400;

            $linhas .= '<row r="'.$linha.'" spans="1:10" ht="'.$altura.'" customHeight="1">'
                .$numero('A'.$linha, 4, $serialData)
                .$texto('B'.$linha, 5, $a->data_realizacao->copy()->locale('pt_BR')->translatedFormat('l'))
                .($inicio ? $numero('C'.$linha, 6, $fracao($inicio)) : '<c r="C'.$linha.'" s="6"/>')
                .($fim ? $numero('D'.$linha, 6, $fracao($fim)) : '<c r="D'.$linha.'" s="6"/>')
                .$numero('E'.$linha, 6, $minutos / 1440)
                .$numero('F'.$linha, 14, $acumuladoMinutos / 1440)
                .'<c r="G'.$linha.'" s="11"/>'
                .$texto('H'.$linha, 7, $descricao)
                .$numero('I'.$linha, 8, $horasDecimais)
                .'<c r="J'.$linha.'" s="9"><f>I'.$linha.'*$J$1</f><v>'.round($valor, 2).'</v></c>'
                .'</row>';
        }

        $ultima = $linha;
        $linha++;
        $linhas .= '<row r="'.$linha.'" spans="1:10">'
            .'<c r="A'.$linha.'" s="10"/><c r="B'.$linha.'" s="10"/><c r="C'.$linha.'" s="10"/><c r="D'.$linha.'" s="10"/><c r="E'.$linha.'" s="10"/><c r="F'.$linha.'" s="10"/>'
            .$texto('G'.$linha, 12, 'Total')
            .'<c r="H'.$linha.'" s="10"/><c r="I'.$linha.'" s="10"/>'
            .'<c r="J'.$linha.'" s="13"><f>SUM(J3:J'.$ultima.')</f><v>'.round($totalValor, 2).'</v></c>'
            .'</row>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n"
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<dimension ref="A1:J'.$linha.'"/>'
            .'<sheetViews><sheetView tabSelected="1" workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<cols><col min="1" max="1" width="10.7109375" customWidth="1"/><col min="2" max="2" width="18.42578125" customWidth="1"/><col min="3" max="3" width="17" customWidth="1"/><col min="4" max="4" width="15.7109375" customWidth="1"/><col min="5" max="5" width="22.85546875" customWidth="1"/><col min="6" max="7" width="22.42578125" customWidth="1"/><col min="8" max="8" width="44.140625" customWidth="1"/><col min="9" max="9" width="15.42578125" customWidth="1"/><col min="10" max="10" width="10.5703125" customWidth="1"/></cols>'
            .'<sheetData>'.$linhas.'</sheetData>'
            .'<pageMargins left="0.511811024" right="0.511811024" top="0.78740157499999996" bottom="0.78740157499999996" header="0.31496062000000002" footer="0.31496062000000002"/>'
            .'</worksheet>';
    }

    /**
     * Acrescenta o estilo 14 (igual ao 6, mas com formato [h]:mm) para as horas acumuladas passarem de 24h.
     */
    private function adicionarEstiloHorasAcumuladas(string $styles): string
    {
        $styles = str_replace(
            '<numFmts count="1">',
            '<numFmts count="2"><numFmt numFmtId="164" formatCode="[h]:mm"/>',
            $styles
        );

        return str_replace(
            '</cellXfs>',
            '<xf numFmtId="164" fontId="4" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf></cellXfs>',
            str_replace('<cellXfs count="14">', '<cellXfs count="15">', $styles)
        );
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
            'descricao' => '',
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

    public function adicionarTextoDescricao(Request $request)
    {
        Gate::authorize('gh.operacional');

        $validated = $request->validate([
            'texto' => 'required|string',
        ]);

        $apontamento = Apontamento::where('user_id', auth()->id())
            ->where('apontamento_ativo', 1)
            ->first();

        if (! $apontamento) {
            return response()->json(['erro' => 'Nenhum apontamento ativo encontrado.'], 422);
        }

        $texto = trim($validated['texto']);
        if ($texto === '') {
            return response()->json(['erro' => 'O texto informado é inválido.'], 422);
        }

        $descricaoAtual = trim((string) $apontamento->descricao);
        $descricaoAtualizada = $descricaoAtual === ''
            ? $texto
            : $descricaoAtual."\n".$texto;

        $apontamento->update([
            'descricao' => $descricaoAtualizada,
        ]);

        return response()->json([
            'success' => true,
            'mensagem' => 'Texto adicionado com sucesso!',
            'descricao' => $descricaoAtualizada,
        ]);
    }
}
