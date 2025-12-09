<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ANT\Models\AntConfiguracao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Modules\ANT\Models\AntEntrega;
use App\Services\IaService;

class CorrecaoController extends Controller
{
    public function edit($idEntrega, $fileIndex = 0)
    {
        $entrega = AntEntrega::with(['trabalho.tipoTrabalho', 'aluno'])->findOrFail($idEntrega);

        // --- NOVA LÓGICA: Lista para o Dropdown de Navegação ---
        // Busca todas as entregas deste trabalho para navegar entre alunos
        // Ordena pelo nome do aluno para facilitar a busca visual
        $listaEntregas = AntEntrega::where('trabalho_id', $entrega->trabalho_id)
            ->join('ant_alunos', 'ant_entregas.aluno_ra', '=', 'ant_alunos.ra')
            ->select('ant_entregas.id', 'ant_entregas.nota', 'ant_alunos.nome', 'ant_alunos.ra')
            ->orderBy('ant_alunos.nome')
            ->get();
        // -------------------------------------------------------

        // Decodifica JSON. Se falhar (arquivos antigos mal formatados), tenta usar como string única
        $arquivos = json_decode($entrega->arquivos, true);
        if (!is_array($arquivos)) {
            // Fallback para casos raros onde a conversão falhou ou é string pura
            $arquivos = $entrega->arquivos ? [$entrega->arquivos] : [];
        }

        // --- NOVO: Preparar Lista de Arquivos com URL Pública para o Sidebar ---
        $arquivosComUrl = [];
        foreach ($arquivos as $arq) {
            $url = $this->getPublicUrl($arq);
            $arquivosComUrl[] = [
                'path' => $arq,
                'url' => $url,
                // Usamos basename() para obter o nome do arquivo, e pathinfo para a extensão
                'nome' => basename($arq),
                'extensao' => strtolower(pathinfo($arq, PATHINFO_EXTENSION)),
            ];
        }
        // -----------------------------------------------------------------------------


        // Lógica de visualização (Mantida a correção anterior)
        if (empty($arquivos)) {
            $dadosVisualizacao = [
                'tipo' => 'texto',
                'conteudo' => "O aluno não anexou nenhum arquivo a esta entrega.\n\nUtilize o painel lateral para atribuir a nota e fornecer o feedback.",
                'linguagem' => 'txt',
                'url' => '#'
            ];
        } else {
            $caminhoArquivo = $arquivos[$fileIndex] ?? $arquivos[0];
            $extensao = strtolower(pathinfo($caminhoArquivo, PATHINFO_EXTENSION));

            $dadosVisualizacao = [
                'tipo' => 'download',
                'conteudo' => null,
                'url' => null
            ];

            $urlPublica = $this->getPublicUrl($caminhoArquivo);

            if (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $dadosVisualizacao['tipo'] = 'imagem';
                $dadosVisualizacao['url'] = $urlPublica;

            } elseif ($extensao === 'pdf') {
                $dadosVisualizacao['tipo'] = 'pdf';
                $dadosVisualizacao['url'] = $urlPublica;

            } elseif (in_array($extensao, ['mp4', 'webm', 'ogg', 'avi', 'mov'])) {
                // --- NOVO: Renderizador para Arquivos de Vídeo Locais
                $dadosVisualizacao['tipo'] = 'video_file';
                $dadosVisualizacao['url'] = $urlPublica;
                // ------------------------------------------

            } elseif (in_array($extensao, ['txt', 'sql', 'cs', 'js', 'html', 'css', 'php', 'py'])) {
                $dadosVisualizacao['tipo'] = 'texto';
                $pathFisico = $this->getPhysicalPath($caminhoArquivo);

                if (file_exists($pathFisico)) {
                    $dadosVisualizacao['conteudo'] = file_get_contents($pathFisico);
                } else {
                    $dadosVisualizacao['conteudo'] = "Erro: Arquivo não encontrado no servidor.\nCaminho: " . $pathFisico;
                }
                $dadosVisualizacao['linguagem'] = $extensao;

            } elseif ($extensao === 'zip') {
                $publicPath = $this->prepararProjetoWeb($caminhoArquivo, $entrega->id, $fileIndex);
                if ($publicPath) {
                    $dadosVisualizacao['tipo'] = 'unity';
                    // USANDO Storage::url()
                    $dadosVisualizacao['url'] = Storage::url($publicPath . '/index.html');
                } else {
                    $dadosVisualizacao['tipo'] = 'download';
                    $dadosVisualizacao['url'] = $urlPublica;
                }
            } elseif ($entrega->trabalho->tipoTrabalho->descricao === 'Link Externo' || str_starts_with($caminhoArquivo, 'http')) {

                // Lógica para Identificar Vídeo (Link Externo)
                $isVideo = str_contains($caminhoArquivo, 'youtube.com') || str_contains($caminhoArquivo, 'youtu.be') || str_contains($caminhoArquivo, 'vimeo.com');

                if ($isVideo) {
                    $dadosVisualizacao['tipo'] = 'video';
                } else {
                    $dadosVisualizacao['tipo'] = 'link';
                }
                $dadosVisualizacao['url'] = $caminhoArquivo;

            } else {
                $dadosVisualizacao['url'] = $urlPublica;
            }
        }

        // Passamos a $listaEntregas e o novo $arquivosComUrl para a view
        return view('ANT::correcao.edit', compact('entrega', 'arquivos', 'fileIndex', 'dadosVisualizacao', 'listaEntregas', 'arquivosComUrl'));
    }

    public function update(Request $request, $idEntrega)
    {
        $request->validate([
            'nota' => 'nullable|numeric|min:0|max:10',
            'comentario_professor' => 'nullable|string',
            'action' => 'nullable|string' // Para identificar qual botão foi clicado
        ]);

        $entregaOriginal = AntEntrega::findOrFail($idEntrega);

        // Lógica de Atualização (com suporte a grupos)
        if (empty($entregaOriginal->arquivos) || $entregaOriginal->arquivos == '[]') {
            $entregaOriginal->update([
                'nota' => $request->nota,
                'comentario_professor' => $request->comentario_professor
            ]);
            $afetados = 1;
        } else {
            $afetados = AntEntrega::where('trabalho_id', $entregaOriginal->trabalho_id)
                ->where('arquivos', $entregaOriginal->arquivos)
                ->update([
                    'nota' => $request->nota,
                    'comentario_professor' => $request->comentario_professor
                ]);
        }

        $msg = $afetados > 1
            ? "Correção salva e replicada para os {$afetados} integrantes do grupo!"
            : "Correção salva com sucesso.";

        // --- NOVA LÓGICA: Salvar e Ir para o Próximo ---
        if ($request->action === 'salvar_proximo') {
            // Busca o próximo aluno deste trabalho que ainda NÃO tem nota
            // Ordenamos por ID ou Nome para manter consistência, mas o importante é pegar quem falta.
            $proximaEntrega = AntEntrega::where('trabalho_id', $entregaOriginal->trabalho_id)
                ->whereNull('nota') // Pega apenas pendentes
                ->where('id', '!=', $idEntrega) // Garante que não é o atual (redundante pois atual já tem nota agora, mas seguro)
                ->first();

            if ($proximaEntrega) {
                return redirect()->route('ant.correcao.edit', $proximaEntrega->id)
                    ->with('success', $msg . ' Carregando próximo aluno pendente...');
            } else {
                // Se não achar ninguém, volta para a lista geral do trabalho
                return redirect()->route('ant.professor.trabalho', $entregaOriginal->trabalho_id)
                    ->with('success', $msg . ' Parabéns! Você concluiu todas as correções deste trabalho.');
            }
        }
        // ------------------------------------------------

        return back()->with('success', $msg);
    }
    /**
     * Retorna a URL acessível pelo navegador
     */
    private function getPublicUrl($path)
    {
        // 1. VERIFICAÇÃO DE LEGADO
        if (str_starts_with($path, '/files/')) {
            // URL Externa do sistema legado
            // Removemos o / inicial para concatenar corretamente se necessário, ou mantemos
            // Ex: /files/2020/arquivo.pdf -> https://spigo.net/trabalhos/files/2020/arquivo.pdf
            return 'https://spigo.net/trabalhos' . $path;
        }

        // 2. Arquivos Novos (Laravel Storage Link)
        // Assume que estão em storage/app/public ou similar e linkados
        return Storage::url($path);
    }

    /**
     * Retorna o caminho FÍSICO no disco (para file_get_contents ou zip_open)
     */
    private function getPhysicalPath($path)
    {
        // 1. VERIFICAÇÃO DE LEGADO
        if (str_starts_with($path, '/files/')) {
            return '/home2/spigo594/public_html/trabalhos' . $path;
        }

        // 2. Arquivos Novos (Storage Local)
        // Se o path for relativo ao disco 'local' (storage/app)
        // Se você usa o disco 'public' (storage/app/public), mude para Storage::disk('public')->path($path)
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        // Tentativa no disco public se não achou no local
        return Storage::disk('public')->path($path);
    }

    /**
     * Define permissões de leitura/escrita para diretórios (0755) e arquivos (0644)
     * recursivamente para garantir acesso pelo web server após a extração do ZIP.
     */
    private function setRecursivePermissions($path)
    {
        if (!is_dir($path)) {
            return;
        }

        // Usamos RecursiveIteratorIterator para percorrer todos os subdiretórios e arquivos
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($items as $item) {
            // 0755 para diretórios, 0644 para arquivos (permissões padrão para web)
            $permission = $item->isDir() ? 0755 : 0644;
            // @chmod ignora erros se não tiver permissão para mudar
            @chmod($item->getRealPath(), $permission);
        }

        // Garante que o diretório raiz da extração também tenha 0755
        @chmod($path, 0755);
    }

    /**
     * Extrai ZIP para pasta pública do LARAVEL para rodar no navegador
     */
    private function prepararProjetoWeb($caminhoZip, $entregaId, $fileIndex)
    {
        $extractPath = "ant/extracted/{$entregaId}_{$fileIndex}";

        // Se já extraiu (cache), retorna o caminho relativo para montar a URL depois
        if (Storage::disk('public')->exists($extractPath . '/index.html')) {
            return $extractPath;
        }

        // Define a origem (Legado ou Novo)
        $fullZipPath = $this->getPhysicalPath($caminhoZip);

        if (!file_exists($fullZipPath)) {
            return null;
        }

        // Define o destino (Sempre no Storage Público do Laravel para o iframe funcionar)
        $destination = Storage::disk('public')->path($extractPath);

        $zip = new \ZipArchive;
        if ($zip->open($fullZipPath) === TRUE) {
            $zip->extractTo($destination);
            $zip->close();

            // 1. Definir permissões após extração para evitar 403
            $this->setRecursivePermissions($destination);

            // --- INÍCIO: Lógica de Descompressão com BrotliHaxe (PHP Puro) ---

            $brotli_library_file = app_path('Modules/ANT/Lib/Brotli/Brotli.php'); // Ajuste o nome do arquivo se necessário
            $brotli_library_dir = app_path('Modules/ANT/Lib/Brotli/');

            // Verifica se a biblioteca PHP puro existe antes de incluir
            if (file_exists($brotli_library_file)) {

                // Inclui a biblioteca BrotliHaxe
                require_once $brotli_library_file;

                // Configuração e descompressão (Assumindo que a classe é 'Brotli' e o método é 'decompressArray')
                try {
                    // Mapeia os dicionários para o diretório de inclusão da biblioteca (necessário para o Haxe port)
                    set_include_path(get_include_path() . PATH_SEPARATOR . $brotli_library_dir);

                    // Instancia o descompressor
                    $brotliDecompressor = new \Brotli();

                    $files = Storage::disk('public')->allFiles($extractPath);

                    foreach ($files as $file) {
                        $fullFilePath = Storage::disk('public')->path($file);

                        // Limpeza de .htaccess
                        if (basename($file) === '.htaccess') {
                            Storage::disk('public')->delete($file);
                            continue;
                        }

                        if (str_ends_with($file, '.br')) {
                            $targetFilePath = substr($fullFilePath, 0, -3); // Ex: de file.js.br para file.js
                            $compressedData = file_get_contents($fullFilePath);

                            // A descompressão espera um byte array, mas strings em PHP são byte arrays no fundo
                            $uncompressedData = $brotliDecompressor->decompress($compressedData);

                            if ($uncompressedData !== false && $uncompressedData !== null) {
                                file_put_contents($targetFilePath, $uncompressedData);
                                Storage::disk('public')->delete($file); // Remove o comprimido
                            } else {
                                // Se falhar, tentamos o fallback de remoção (Unity tentará o não comprimido)
                                Storage::disk('public')->delete($file);
                            }
                        }
                        // Limpeza de Gzip
                        elseif (str_ends_with($file, '.gz')) {
                            Storage::disk('public')->delete($file);
                        }
                    }
                    // Restaura o include path após o uso
                    restore_include_path();

                } catch (\Exception $e) {
                    // Em caso de erro na biblioteca (ex: dicionário faltando), loga o erro e faz fallback
                    Log::error("BrotliHaxe Decoding Error: " . $e->getMessage());
                    // Limpa arquivos .br para forçar Unity a tentar a versão descompactada (último recurso)
                    $files = Storage::disk('public')->allFiles($extractPath);
                    foreach ($files as $file) {
                        if (str_ends_with($file, '.br')) {
                            Storage::disk('public')->delete($file);
                        }
                    }
                }
            } else {
                // Se a biblioteca não foi encontrada, faz o fallback de remoção que o cliente tinha antes
                $files = Storage::disk('public')->allFiles($extractPath);
                foreach ($files as $file) {
                    if (str_ends_with($file, '.br') || str_ends_with($file, '.gz')) {
                        Storage::disk('public')->delete($file);
                    }
                }
            }
            // --- FIM: Lógica de Descompressão com BrotliHaxe (PHP Puro) ---

            // 3. Procura recursivamente por index.html
            $files = Storage::disk('public')->allFiles($extractPath);
            foreach ($files as $file) {
                if (basename($file) === 'index.html') {
                    return dirname($file);
                }
            }

            return $extractPath;
        } else {
            return null;
        }
    }
    public function iaSugestao(Request $request, $idEntrega)
    {
        $entrega = AntEntrega::with(['trabalho', 'aluno'])->findOrFail($idEntrega);
        $config = AntConfiguracao::first();

        // 1. Preparar Conteúdo do Aluno
        // Vamos ler todos os arquivos de TEXTO que o aluno enviou e concatenar
        $arquivos = json_decode($entrega->arquivos, true) ?? [];
        $conteudoAluno = "";
        $arquivosLidos = 0;

        foreach ($arquivos as $arq) {
            $ext = strtolower(pathinfo($arq, PATHINFO_EXTENSION));
            // Apenas tentamos ler arquivos de código ou texto
            if (in_array($ext, ['txt', 'sql', 'php', 'js', 'css', 'html', 'java', 'py', 'cs', 'json', 'md'])) {
                $path = $this->getPhysicalPath($arq);
                if (file_exists($path)) {
                    $conteudoAluno .= "\n--- Arquivo: " . basename($arq) . " ---\n";
                    $conteudoAluno .= file_get_contents($path);
                    $arquivosLidos++;
                }
            } else {
                $conteudoAluno .= "\n--- Arquivo: " . basename($arq) . " (Conteúdo binário/não lido) ---\n";
            }
        }

        if ($arquivosLidos === 0 && empty($conteudoAluno)) {
            return response()->json(['error' => 'Não foi possível ler o conteúdo dos arquivos para enviar à IA.'], 400);
        }

        // 2. Montar o Prompt
        $systemPrompt = $config->prompt_agente ??
            "Você é um professor universitário experiente e justo. Avalie o trabalho do aluno com base na descrição e dicas fornecidas. Retorne estritamente um JSON.";

        $userPrompt = <<<EOT
CONTEXTO DA AVALIAÇÃO:
Título do Trabalho: {$entrega->trabalho->nome}
Descrição do Trabalho: {$entrega->trabalho->descricao}
Dicas/Critérios de Correção: {$entrega->trabalho->dicas_correcao}
Nome do Aluno: {$entrega->aluno->nome}

||| INICIO RESPOSTA DO ALUNO |||
{$conteudoAluno}
||| FIM RESPOSTA DO ALUNO |||
TAREFA:
Analise o código/texto entre ||| INICIO RESPOSTA DO ALUNO ||| e ||| FIM RESPOSTA DO ALUNO |||, se não existir conteúdo do aluno, não invete, dê nota 0 e avise que não foi possível avaliar.
O Feedback para o aluno deve ser construtivo, apontando pontos fortes e fracos, e sugerindo melhorias. Caso não consiga avaliar, explique o motivo.
Sua resposta deve conter APENAS o seguinte formato JSON com dois campos:
1. Atribua uma nota de 0 a 10 (float).
2. Escreva um feedback construtivo com um toque de humor justificando a nota e apontando melhorias.


FORMATO DE SAÍDA (JSON OBRIGATÓRIO):
{
    "nota": 0.0,
    "feedback": "Seu texto aqui..."
}
Responda APENAS o JSON, sem markdown (```json) ou texto adicional.
EOT;

        // 3. Chamar o Serviço
        $service = new IaService();
        $respostaIa = $service->generateText([
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ], [
            'jsonMode' => true, // O Pollination/OpenAI suporta json_object mode se o modelo permitir
            'temperature' => 1
        ]);

        if (!$respostaIa) {
            return response()->json(['error' => 'A IA não retornou uma resposta válida.'], 500);
        }

        // 4. Tratamento do JSON (às vezes a IA coloca ```json ... ```)
        $jsonLimpo = preg_replace('/^```json|```$/m', '', $respostaIa);
        $dados = json_decode($jsonLimpo, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Erro JSON IA: " . $respostaIa);
            return response()->json(['error' => 'Falha ao processar o JSON da IA.', 'raw' => $respostaIa], 500);
        }

        return response()->json($dados);
    }


}
