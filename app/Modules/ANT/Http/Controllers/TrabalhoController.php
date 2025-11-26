<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Modules\ANT\Models\AntTrabalho;
use App\Modules\ANT\Models\AntEntrega;
use App\Modules\ANT\Models\AntAluno;

class TrabalhoController extends Controller
{
    /**
     * Exibe os detalhes do trabalho e formulário de entrega
     */
    public function show($id)
    {
        $user = auth()->user();

        // Busca o aluno vinculado ao usuário logado
        $aluno = AntAluno::where('user_id', $user->id)->firstOrFail();

        // Busca o trabalho e carrega a entrega ESPECÍFICA deste aluno (usando RA)
        $trabalho = AntTrabalho::with(['materia', 'tipoTrabalho', 'entregas' => function($q) use ($aluno) {
            // CORREÇÃO: Filtra pela coluna 'aluno_ra' em vez de 'aluno_id'
            $q->where('aluno_ra', $aluno->ra);
        }])->findOrFail($id);

        // Verificação de Segurança: O aluno pertence à matéria?
        // O relacionamento materias() no model AntAluno já usa o RA, então isso funciona direto
        $matriculado = $aluno->materias()
            ->where('ant_materias.id', $trabalho->materia_id)
            ->exists();

        if (!$matriculado) {
            abort(403, 'Você não está matriculado nesta disciplina.');
        }

        // Recupera entrega anterior (se houver) para exibir na tela
        $entrega = $trabalho->entregas->first();

        // Verifica status do prazo
        $prazo = \Carbon\Carbon::parse($trabalho->prazo)->endOfDay();
        $isAtrasado = now()->gt($prazo);

        return view('ANT::trabalhos.show', compact('trabalho', 'entrega', 'aluno', 'isAtrasado'));
    }

    /**
     * Processa o envio do trabalho
     */
    public function store(Request $request, $id)
    {
        $user = auth()->user();
        $aluno = AntAluno::where('user_id', $user->id)->firstOrFail();
        $trabalho = AntTrabalho::with('tipoTrabalho')->findOrFail($id);

        // --- NOVA VERIFICAÇÃO DE BLOQUEIO ---
        // Verifica se já existe entrega para este trabalho e se JÁ TEM NOTA
        $entregaExistente = AntEntrega::where('trabalho_id', $trabalho->id)
            ->where('aluno_ra', $aluno->ra)
            ->first();

        if ($entregaExistente && !is_null($entregaExistente->nota)) {
            return back()->withErrors(['erro' => 'Este trabalho já foi corrigido e avaliado. Não é possível reenviar.']);
        }
        // ------------------------------------

        // Validação básica
        $request->validate([
            'comentario_aluno' => 'nullable|string',
            'arquivos.*' => 'nullable|file|max:10240', // Max 10MB por arquivo
            'link' => 'nullable|url'
        ]);

        // Verifica tipos permitidos (ex: "pdf|zip" ou "link")
        $tiposPermitidos = explode('|', $trabalho->tipoTrabalho->arquivos);
        $ehLink = in_array('link', $tiposPermitidos);

        $caminhos = [];

        // 1. Processar Link
        if ($ehLink && $request->filled('link')) {
            $caminhos[] = $request->link;
        }

        // 2. Processar Arquivos (Upload)
        if ($request->hasFile('arquivos')) {
            foreach ($request->file('arquivos') as $arquivo) {
                // Validação manual de extensão
                if (!$ehLink && !in_array($arquivo->getClientOriginalExtension(), $tiposPermitidos)) {
                    return back()->withErrors(['arquivos' => "O tipo de arquivo .{$arquivo->getClientOriginalExtension()} não é permitido. Aceitos: " . implode(', ', $tiposPermitidos)]);
                }

                // Salva em storage/app/ant/entregas/{semestre}/{materia}/{trabalho}/{ra}
                // Usamos o RA na estrutura de pastas também para manter organizado
                $path = $arquivo->storeAs(
                    "ant/entregas/{$trabalho->semestre}/{$trabalho->materia->nome_curto}/{$trabalho->id}/{$aluno->ra}",
                    $arquivo->getClientOriginalName(),
                    'local'
                );
                $caminhos[] = $path;
            }
        }

        if (empty($caminhos)) {
            return back()->withErrors(['arquivos' => 'Você deve enviar pelo menos um arquivo ou link.']);
        }

        // 3. Salvar ou Atualizar Entrega (USANDO RA)
        AntEntrega::updateOrCreate(
            [
                'trabalho_id' => $trabalho->id,
                'aluno_ra'    => $aluno->ra // CORREÇÃO: Chave agora é o RA
            ],
            [
                'arquivos' => json_encode($caminhos),
                'comentario_aluno' => $request->comentario_aluno,
                'data_entrega' => now(),
                // Nota e comentário do professor não são alterados aqui
            ]
        );

        return redirect()->route('ant.trabalhos.show', $id)->with('success', 'Trabalho entregue com sucesso!');
    }
}
