<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Models\AntAluno;
use App\Modules\ANT\Models\AntConfiguracao;

class AdminAlunoController extends Controller
{
    public function index(Request $request)
    {
        $materias = AntMateria::orderBy('nome')->get();

        // Configuração padrão
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        // Filtros da Requisição (ou padrão)
        $filtroSemestre = $request->input('semestre', $semestreAtual);
        $filtroMateria = $request->input('materia_id');

        $alunos = collect([]);

        if ($filtroMateria) {
            // Busca na tabela pivô e junta com a tabela de alunos para pegar o nome
            $alunos = DB::table('ant_aluno_materia')
                ->join('ant_alunos', 'ant_aluno_materia.aluno_ra', '=', 'ant_alunos.ra')
                ->where('ant_aluno_materia.materia_id', $filtroMateria)
                ->where('ant_aluno_materia.semestre', $filtroSemestre)
                ->select(
                    'ant_aluno_materia.id as matricula_id', // ID para exclusão
                    'ant_alunos.ra',
                    'ant_alunos.nome',
                    'ant_aluno_materia.created_at'
                )
                ->orderBy('ant_alunos.nome')
                ->get();
        }

        return view('ANT::admin.alunos.index', compact('materias', 'alunos', 'filtroSemestre', 'filtroMateria'));
    }

    // Remover Matrícula
    public function destroy($id)
    {
        // Remove apenas o vínculo daquela matéria/semestre
        // O cadastro do aluno (RA/Nome) permanece no banco
        DB::table('ant_aluno_materia')->where('id', $id)->delete();

        return back()->with('success', 'Matrícula removida com sucesso.');
    }
    // Tela de Importação
    public function importar()
    {
        $materias = AntMateria::orderBy('nome')->get();

        // Pega semestre atual para sugerir
        $config = AntConfiguracao::first();
        $semestreAtual = $config->semestre_atual ?? date('Y') . '-' . (date('m') > 6 ? '2' : '1');

        return view('ANT::admin.alunos.importar', compact('materias', 'semestreAtual'));
    }

    // Processamento da Lista
    public function processarImportacao(Request $request)
    {
        $request->validate([
            'materia_id' => 'required|exists:ant_materias,id',
            'semestre' => 'required|string|max:10',
            'lista_alunos' => 'required|string',
        ]);

        $linhas = explode("\n", $request->lista_alunos);
        $importados = 0;
        $matriculados = 0;
        $erros = [];

        DB::beginTransaction();

        try {
            foreach ($linhas as $linha) {
                $linha = trim($linha);
                if (empty($linha)) continue;

                // Tenta extrair RA e Nome
                // Padrão aceito: "NUMEROS [separador opcional] NOME"
                // Ex: "123456 Fulano de Tal" ou "123456 - Fulano"
                if (preg_match('/^(\d+)\s*[-–]?\s*(.+)$/', $linha, $matches)) {
                    $ra = trim($matches[1]);
                    $nome = trim($matches[2]);

                    // 1. Cria ou Atualiza o Aluno (Baseado no RA)
                    // Se o aluno já existe, atualizamos o nome para garantir que está atualizado
                    // O 'user_id' permanece inalterado (ou null) até o aluno vincular a conta dele
                    $aluno = AntAluno::updateOrCreate(
                        ['ra' => $ra],
                        ['nome' => $nome]
                    );

                    if ($aluno->wasRecentlyCreated) {
                        $importados++;
                    }

                    // 2. Vincular à Matéria (Matrícula)
                    // Verifica se já existe vínculo para evitar erro de Unique Key
                    $vinculoExiste = DB::table('ant_aluno_materia')
                        ->where('aluno_ra', $ra)
                        ->where('materia_id', $request->materia_id)
                        ->where('semestre', $request->semestre)
                        ->exists();

                    if (!$vinculoExiste) {
                        DB::table('ant_aluno_materia')->insert([
                            'aluno_ra' => $ra,
                            'materia_id' => $request->materia_id,
                            'semestre' => $request->semestre,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $matriculados++;
                    }

                } else {
                    // Se a linha não bater com o padrão (ex: cabeçalho "RA NOME"), ignora ou loga
                    // $erros[] = "Formato inválido: $linha";
                }
            }

            DB::commit();

            $msg = "Processo concluído! $importados novos alunos cadastrados e $matriculados novas matrículas realizadas.";
            return back()->with('success', $msg)->with('detalhes_erros', $erros);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao importar: ' . $e->getMessage())->withInput();
        }
    }
}
