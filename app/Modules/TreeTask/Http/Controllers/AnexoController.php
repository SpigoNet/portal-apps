<?php

namespace App\Modules\TreeTask\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TreeTask\Models\Anexo;
use App\Modules\TreeTask\Models\Tarefa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnexoController extends Controller
{
    /**
     * Realiza o upload e vincula à tarefa.
     */
    public function store(Request $request, $id_tarefa)
    {
        $request->validate([
            'arquivo' => 'required|file|max:10240', // Max 10MB
        ]);

        $tarefa = Tarefa::findOrFail($id_tarefa);
        $file = $request->file('arquivo');

        // 1. Salvar arquivo no disco (pasta 'treetask_uploads')
        $path = $file->store('treetask_uploads'); // O padrão geralmente é storage/app/

        // 2. Criar registro na tabela treetask_anexos
        $anexo = Anexo::create([
            'id_user_upload' => auth()->id(),
            'nome_arquivo' => $file->getClientOriginalName(),
            'path_arquivo' => $path,
            'mime_type' => $file->getMimeType(),
            'tamanho' => $file->getSize(),
        ]);

        // 3. Vincular à tarefa (tabela pivô)
        $tarefa->anexos()->attach($anexo->id_anexo);

        return redirect()->route('treetask.tarefas.show', $id_tarefa)
            ->with('success', 'Arquivo anexado com sucesso.');
    }

    /**
     * Download do arquivo.
     */
    public function download($id_anexo)
    {
        $anexo = Anexo::findOrFail($id_anexo);

        // Verificação de segurança básica: O arquivo existe?
        if (!Storage::exists($anexo->path_arquivo)) {
            abort(404, 'Arquivo físico não encontrado.');
        }

        return Storage::download($anexo->path_arquivo, $anexo->nome_arquivo);
    }

    /**
     * Remove o anexo.
     */
    public function destroy($id_tarefa, $id_anexo)
    {
        $anexo = Anexo::findOrFail($id_anexo);

        // Remove o vínculo (pivô)
        $tarefa = Tarefa::findOrFail($id_tarefa);
        $tarefa->anexos()->detach($id_anexo);

        // Opcional: Verificar se o anexo está ligado a outras tarefas/projetos antes de deletar o arquivo físico.
        // Como sua tabela é Many-to-Many, é seguro apenas desvincular (detach).
        // Se quiser deletar o arquivo físico quando não houver mais vínculos:
        if ($anexo->tarefas()->count() == 0) {
            Storage::delete($anexo->path_arquivo);
            $anexo->delete();
        }

        return redirect()->route('treetask.tarefas.show', $id_tarefa)
            ->with('success', 'Anexo removido.');
    }
}
