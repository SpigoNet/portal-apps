<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Modules\ANT\Models\AntEntrega;

class CorrecaoController extends Controller
{
    public function edit($idEntrega, $fileIndex = 0)
    {
        $entrega = AntEntrega::with(['trabalho.tipoTrabalho', 'aluno'])->findOrFail($idEntrega);

        // Decodifica JSON. Se falhar (arquivos antigos mal formatados), tenta usar como string única
        $arquivos = json_decode($entrega->arquivos, true);
        if (!is_array($arquivos)) {
            // Fallback para casos raros onde a conversão falhou ou é string pura
            $arquivos = $entrega->arquivos ? [$entrega->arquivos] : [];
        }

        if (empty($arquivos)) {
            return back()->with('error', 'Esta entrega não possui arquivos.');
        }

        $caminhoArquivo = $arquivos[$fileIndex] ?? $arquivos[0];
        $extensao = strtolower(pathinfo($caminhoArquivo, PATHINFO_EXTENSION));

        $dadosVisualizacao = [
            'tipo' => 'download',
            'conteudo' => null,
            'url' => null
        ];

        // Lógica de URL Pública (Detecta Legado vs Novo)
        $urlPublica = $this->getPublicUrl($caminhoArquivo);

        if (in_array($extensao, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $dadosVisualizacao['tipo'] = 'imagem';
            $dadosVisualizacao['url'] = $urlPublica;

        } elseif ($extensao === 'pdf') {
            $dadosVisualizacao['tipo'] = 'pdf';
            $dadosVisualizacao['url'] = $urlPublica;

        } elseif (in_array($extensao, ['txt', 'sql', 'cs', 'js', 'html', 'css', 'php', 'py'])) {
            $dadosVisualizacao['tipo'] = 'texto';
            // Para ler o conteúdo, precisamos do caminho físico real
            $pathFisico = $this->getPhysicalPath($caminhoArquivo);

            if (file_exists($pathFisico)) {
                $dadosVisualizacao['conteudo'] = file_get_contents($pathFisico);
            } else {
                $dadosVisualizacao['conteudo'] = "Erro: Arquivo não encontrado no servidor.\nCaminho: " . $pathFisico;
            }
            $dadosVisualizacao['linguagem'] = $extensao;

        } elseif ($extensao === 'zip') {
            // Se for ZIP legado ou novo, a lógica de extração precisa saber onde buscar
            $publicPath = $this->prepararProjetoWeb($caminhoArquivo, $entrega->id, $fileIndex);

            if ($publicPath) {
                $dadosVisualizacao['tipo'] = 'unity'; // Ou genérico web
                // A URL do iframe deve apontar para o index.html extraído dentro do storage público do Laravel
                $dadosVisualizacao['url'] = asset('storage/' . $publicPath . '/index.html');
            } else {
                // Se falhar extração, vira download normal
                $dadosVisualizacao['tipo'] = 'download';
                $dadosVisualizacao['url'] = $urlPublica;
            }
        } elseif ($entrega->trabalho->tipoTrabalho->descricao === 'Link Externo' || str_starts_with($caminhoArquivo, 'http')) {
            $dadosVisualizacao['tipo'] = 'link';
            $dadosVisualizacao['url'] = $caminhoArquivo;
        } else {
            // Default Download
            $dadosVisualizacao['url'] = $urlPublica;
        }

        return view('ANT::correcao.edit', compact('entrega', 'arquivos', 'fileIndex', 'dadosVisualizacao'));
    }

    public function update(Request $request, $idEntrega)
    {
        $entrega = AntEntrega::findOrFail($idEntrega);

        $entrega->update([
            'nota' => $request->nota,
            'comentario_professor' => $request->comentario_professor
        ]);

        return back()->with('success', 'Correção salva com sucesso.');
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

            // Procura recursivamente por index.html
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
}
