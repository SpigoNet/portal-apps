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
        $trabalho = AntTrabalho::with([
            'materia',
            'tipoTrabalho',
            'entregas' => function ($q) use ($aluno) {
                // CORREÇÃO: Filtra pela coluna 'aluno_ra' em vez de 'aluno_id'
                $q->where('aluno_ra', $aluno->ra);
            }
        ])->findOrFail($id);

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
        $alunoLider = AntAluno::where('user_id', $user->id)->firstOrFail();
        $trabalho = AntTrabalho::with('tipoTrabalho')->findOrFail($id);

        // ... (Verificações de Bloqueio de Nota existentes) ...

        // Validação básica
        $request->validate([
            'comentario_aluno' => 'nullable|string',
            'arquivos.*' => 'nullable|file|max:10240',
            'link' => 'nullable|url',
            'integrantes' => 'nullable|array' // Valida o array de RAs
        ]);

        // 1. Lista de RAs para entregar (Líder + Integrantes)
        $rasParaEntregar = [$alunoLider->ra]; // Começa com o líder

        if ($request->has('integrantes')) {
            foreach ($request->integrantes as $raColega) {
                // Validação de Segurança: O colega existe e é da matéria?
                // Isso impede que injetem RA de outra pessoa aleatória
                $existeNaMateria = AntAluno::where('ra', $raColega)
                    ->whereHas('materias', function ($q) use ($trabalho) {
                        $q->where('ant_materias.id', $trabalho->materia_id);
                    })->exists();

                if ($existeNaMateria) {
                    $rasParaEntregar[] = $raColega;
                }
            }
        }

        // Validação de Quantidade
        $rasParaEntregar = array_unique($rasParaEntregar); // Remove duplicados
        if (count($rasParaEntregar) > $trabalho->maximo_alunos) {
            return back()->withErrors(['integrantes' => 'O número de integrantes excede o permitido.']);
        }

        // 2. Processamento de Arquivos (FAZ UMA ÚNICA VEZ)
        $tiposPermitidos = explode('|', strtoupper($trabalho->tipoTrabalho->arquivos));
        $ehLink = in_array('LINK', $tiposPermitidos);
        $caminhos = [];

        if ($ehLink && $request->filled('link')) {
            $caminhos[] = $request->link;
        }

        if ($request->hasFile('arquivos')) {
            foreach ($request->file('arquivos') as $arquivo) {
                $extensaoDoArquivo = strtoupper($arquivo->getClientOriginalExtension());
                if (!$ehLink && !in_array($extensaoDoArquivo, $tiposPermitidos)) {
                    return back()->withErrors(['arquivos' => "Extensão inválida."]);
                }

                // Salva no diretório do LÍDER (para organização)
                $targetPath = "ant/entregas/{$trabalho->semestre}/{$trabalho->materia->nome_curto}/{$trabalho->id}/{$alunoLider->ra}";
                $fileName = $arquivo->getClientOriginalName();

                \Log::info('SFTP Upload Attempt', [
                    'target_path' => $targetPath,
                    'file_name' => $fileName,
                    'full_path' => $targetPath . '/' . $fileName
                ]);

                // Cria a estrutura de diretórios no SFTP se não existir
                try {
                    if (!\Storage::disk('sftp')->exists($targetPath)) {
                        \Storage::disk('sftp')->makeDirectory($targetPath);
                        \Log::info('SFTP Directory Created', ['path' => $targetPath]);
                    }
                } catch (\Exception $e) {
                    \Log::error('SFTP Directory Creation Failed', [
                        'path' => $targetPath,
                        'error' => $e->getMessage()
                    ]);
                }

                $path = $arquivo->storeAs(
                    $targetPath,
                    $fileName,
                    'sftp' // Alterado para SFTP
                );

                \Log::info('SFTP Upload Result', [
                    'returned_path' => $path,
                    'exists' => \Storage::disk('sftp')->exists($path)
                ]);

                $caminhos[] = $path;
            }
        }

        if (empty($caminhos)) {
            return back()->withErrors(['arquivos' => 'Envie pelo menos um arquivo ou link.']);
        }

        $jsonArquivos = json_encode($caminhos);

        // 3. Salvar Entrega para TODOS os integrantes
        // O loop cria uma linha para cada aluno, mas todas compartilham o $jsonArquivos
        foreach ($rasParaEntregar as $ra) {
            AntEntrega::updateOrCreate(
                [
                    'trabalho_id' => $trabalho->id,
                    'aluno_ra' => $ra
                ],
                [
                    'arquivos' => $jsonArquivos, // Mesmo caminho para todos
                    'comentario_aluno' => $request->comentario_aluno . ($ra === $alunoLider->ra ? " (Enviado pelo Líder)" : " (Enviado via Grupo por {$alunoLider->nome})"),
                    'data_entrega' => now(),
                ]
            );
        }

        return redirect()->route('ant.trabalhos.show', $id)->with('success', 'Trabalho entregue para todo o grupo com sucesso!');
    }

    public function buscarColegas(Request $request)
    {
        $termo = $request->query('q');
        $materiaId = $request->query('materia_id');
        $user = auth()->user();
        $alunoLogado = AntAluno::where('user_id', $user->id)->firstOrFail();

        if (strlen($termo) < 3) {
            return response()->json([]);
        }

        // Busca alunos que:
        // 1. Tenham nome ou RA parecido com o termo
        // 2. Estejam matriculados NA MESMA MATÉRIA que o trabalho exige
        // 3. NÃO sejam o próprio aluno logado
        $alunos = AntAluno::where(function ($q) use ($termo) {
            $q->where('nome', 'like', "%{$termo}%")
                ->orWhere('ra', 'like', "%{$termo}%");
        })
            ->whereHas('materias', function ($q) use ($materiaId) {
                $q->where('ant_materias.id', $materiaId);
            })
            ->where('id', '!=', $alunoLogado->id)
            ->take(10)
            ->get(['ra', 'nome']);

        return response()->json($alunos);
    }
}
