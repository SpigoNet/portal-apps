<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ANT\Models\AntAluno;
use App\Modules\ANT\Models\AntConfiguracao;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Models\AntMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MaterialController extends Controller
{
    /**
     * Lista materiais de uma disciplina, agrupados por data de aula.
     * Acessível por professores da disciplina e alunos matriculados.
     */
    public function index($idMateria)
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        $materia = AntMateria::findOrFail($idMateria);

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$ehProfessor) {
            $aluno = AntAluno::where('user_id', $user->id)->first();
            if (!$aluno) {
                abort(403, 'Acesso negado.');
            }
            $matriculado = $aluno->materias()
                ->where('ant_materias.id', $idMateria)
                ->exists();
            if (!$matriculado) {
                abort(403, 'Você não está matriculado nesta disciplina.');
            }
        }

        $materiais = AntMaterial::where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->with('professor')
            ->orderBy('data_aula', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupa por data_aula (string YYYY-MM-DD)
        $materiaisAgrupados = $materiais->groupBy('data_aula');

        return view('ANT::materiais.index', compact('materia', 'materiaisAgrupados', 'semestreAtual', 'ehProfessor'));
    }

    /**
     * Formulário de upload de novo material (professor).
     */
    public function create($idMateria)
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        $materia = AntMateria::findOrFail($idMateria);

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$ehProfessor) {
            abort(403, 'Apenas professores podem publicar materiais.');
        }

        return view('ANT::materiais.create', compact('materia', 'semestreAtual'));
    }

    /**
     * Salva novo material com upload para SFTP (mesmo padrão das entregas de alunos).
     */
    public function store(Request $request, $idMateria)
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        $materia = AntMateria::findOrFail($idMateria);

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $idMateria)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$ehProfessor) {
            abort(403, 'Apenas professores podem publicar materiais.');
        }

        $request->validate([
            'titulo'    => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'data_aula' => 'required|date',
            'arquivos'  => 'nullable|array',
            'arquivos.*' => 'required|file|max:51200',
            'videos'    => 'nullable|array',
            'videos.*'  => 'nullable|url|max:500',
        ]);

        $caminhos = [];

        if ($request->hasFile('arquivos')) {
            $targetPath = "ant/materiais/{$semestreAtual}/{$materia->nome_curto}/{$request->data_aula}";

            try {
                if (!Storage::disk('sftp')->exists($targetPath)) {
                    Storage::disk('sftp')->makeDirectory($targetPath);
                }
            } catch (\Exception $e) {
                \Log::error('SFTP Material Directory Creation Failed', [
                    'path'  => $targetPath,
                    'error' => $e->getMessage(),
                ]);
            }

            foreach ($request->file('arquivos') as $arquivo) {
                $fileName = $arquivo->getClientOriginalName();
                $path = $arquivo->storeAs($targetPath, $fileName, 'sftp');
                $caminhos[] = $path;

                \Log::info('SFTP Material Upload', [
                    'path'   => $path,
                    'exists' => Storage::disk('sftp')->exists($path),
                ]);
            }
        }

        $videos = collect($request->input('videos', []))
            ->filter(fn($v) => !empty(trim($v)))
            ->values()
            ->all();

        AntMaterial::create([
            'materia_id' => $idMateria,
            'user_id'    => $user->id,
            'semestre'   => $semestreAtual,
            'data_aula'  => $request->data_aula,
            'titulo'     => $request->titulo,
            'descricao'  => $request->descricao,
            'arquivos'   => !empty($caminhos) ? json_encode($caminhos) : null,
            'videos'     => !empty($videos) ? json_encode($videos) : null,
        ]);

        return redirect()->route('ant.materiais.index', $idMateria)
            ->with('success', 'Material publicado com sucesso!');
    }

    /**
     * Formulário de edição de material (professor).
     */
    public function edit($id)
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        $material = AntMaterial::findOrFail($id);
        $materia = $material->materia;

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $material->materia_id)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$ehProfessor) {
            abort(403, 'Apenas professores podem editar materiais.');
        }

        $arquivosExistentes = json_decode($material->arquivos, true) ?? [];
        $videosExistentes   = json_decode($material->videos, true) ?? [];

        return view('ANT::materiais.edit', compact('material', 'materia', 'semestreAtual', 'arquivosExistentes', 'videosExistentes'));
    }

    /**
     * Atualiza material: edita campos, adiciona/remove anexos e vídeos.
     */
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        $material = AntMaterial::findOrFail($id);
        $materia  = $material->materia;

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $material->materia_id)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$ehProfessor) {
            abort(403, 'Apenas professores podem editar materiais.');
        }

        $request->validate([
            'titulo'           => 'required|string|max:255',
            'descricao'        => 'nullable|string',
            'data_aula'        => 'required|date',
            'novos_arquivos'   => 'nullable|array',
            'novos_arquivos.*' => 'required|file|max:51200',
            'remover_arquivos' => 'nullable|array',
            'remover_arquivos.*' => 'nullable|string',
            'videos'           => 'nullable|array',
            'videos.*'         => 'nullable|url|max:500',
        ]);

        // Arquivos: mantém os existentes menos os marcados para remoção
        $arquivosAtuais = json_decode($material->arquivos, true) ?? [];
        $remover = $request->input('remover_arquivos', []);

        foreach ($remover as $caminho) {
            try {
                if (Storage::disk('sftp')->exists($caminho)) {
                    Storage::disk('sftp')->delete($caminho);
                }
            } catch (\Exception $e) {
                \Log::error('SFTP Material Delete Failed', [
                    'path'  => $caminho,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $arquivosAtuais = array_values(array_diff($arquivosAtuais, $remover));

        // Novos arquivos
        if ($request->hasFile('novos_arquivos')) {
            $targetPath = "ant/materiais/{$semestreAtual}/{$materia->nome_curto}/{$request->data_aula}";

            try {
                if (!Storage::disk('sftp')->exists($targetPath)) {
                    Storage::disk('sftp')->makeDirectory($targetPath);
                }
            } catch (\Exception $e) {
                \Log::error('SFTP Material Directory Creation Failed', [
                    'path'  => $targetPath,
                    'error' => $e->getMessage(),
                ]);
            }

            foreach ($request->file('novos_arquivos') as $arquivo) {
                $fileName = $arquivo->getClientOriginalName();
                $path = $arquivo->storeAs($targetPath, $fileName, 'sftp');
                $arquivosAtuais[] = $path;

                \Log::info('SFTP Material Upload', [
                    'path'   => $path,
                    'exists' => Storage::disk('sftp')->exists($path),
                ]);
            }
        }

        $videos = collect($request->input('videos', []))
            ->filter(fn($v) => !empty(trim($v)))
            ->values()
            ->all();

        $material->update([
            'data_aula' => $request->data_aula,
            'titulo'    => $request->titulo,
            'descricao' => $request->descricao,
            'arquivos'  => !empty($arquivosAtuais) ? json_encode($arquivosAtuais) : null,
            'videos'    => !empty($videos) ? json_encode($videos) : null,
        ]);

        return redirect()->route('ant.materiais.index', $material->materia_id)
            ->with('success', 'Material atualizado com sucesso!');
    }

    /**
     * Remove um material e seus arquivos do SFTP (professor).
     */
    public function destroy($id)
    {
        $user = auth()->user();
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        $material = AntMaterial::findOrFail($id);

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $material->materia_id)
            ->where('semestre', $semestreAtual)
            ->exists();

        if (!$ehProfessor) {
            abort(403, 'Apenas professores podem remover materiais.');
        }

        $arquivos = json_decode($material->arquivos, true) ?? [];
        foreach ($arquivos as $caminho) {
            try {
                if (Storage::disk('sftp')->exists($caminho)) {
                    Storage::disk('sftp')->delete($caminho);
                }
            } catch (\Exception $e) {
                \Log::error('SFTP Material Delete Failed', [
                    'path'  => $caminho,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $material->delete();

        return back()->with('success', 'Material removido com sucesso.');
    }
}
