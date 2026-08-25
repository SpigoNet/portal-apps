<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ANT\Models\AntAluno;
use App\Modules\ANT\Models\AntApresentacao;
use App\Modules\ANT\Models\AntApresentacaoAgendamento;
use App\Modules\ANT\Models\AntApresentacaoApresentador;
use App\Modules\ANT\Models\AntEstrela;
use App\Modules\ANT\Models\AntMateria;
use App\Modules\ANT\Models\AntPeso;
use App\Modules\ANT\Services\SemestreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApresentacaoController extends Controller
{
    /**
     * Verifica se o usuário leciona a matéria (ou é admin do app).
     */
    private function podeAcessarMateria(int $materiaId, string $semestre): bool
    {
        $user = auth()->user();

        $ehProfessor = DB::table('ant_professor_materia')
            ->where('user_id', $user->id)
            ->where('materia_id', $materiaId)
            ->where('semestre', $semestre)
            ->exists();

        $config = \App\Modules\ANT\Models\AntConfiguracao::first();
        $isAdmin = $config && $config->isAdmin($user->email);

        return $ehProfessor || $isAdmin;
    }

    private function materiasDoProfessor(string $semestre)
    {
        $user = auth()->user();

        return AntMateria::whereHas('professores', function ($q) use ($user, $semestre) {
            $q->where('user_id', $user->id)->where('semestre', $semestre);
        })->get();
    }

    // Lista de apresentações do professor
    public function index()
    {
        $user = auth()->user();
        $semestreAtual = SemestreService::getForUser($user);

        $materias = $this->materiasDoProfessor($semestreAtual)->pluck('id');

        $apresentacoes = AntApresentacao::whereIn('materia_id', $materias)
            ->where('semestre', $semestreAtual)
            ->with(['materia', 'agendamentos'])
            ->orderBy('nome')
            ->get();

        return view('ANT::professores.apresentacoes.index', compact('apresentacoes', 'semestreAtual'));
    }

    // Formulário de nova apresentação
    public function create()
    {
        $user = auth()->user();
        $semestreAtual = SemestreService::getForUser($user);

        $materias = $this->materiasDoProfessor($semestreAtual);

        if ($materias->isEmpty()) {
            return redirect()->route('ant.professor.apresentacoes.index')
                ->with('error', 'Você não está vinculado a nenhuma matéria neste semestre.');
        }

        $pesos = AntPeso::whereIn('materia_id', $materias->pluck('id'))
            ->where('semestre', $semestreAtual)
            ->with('materia')
            ->get();

        return view('ANT::professores.apresentacoes.create', compact('materias', 'pesos', 'semestreAtual'));
    }

    // Salvar nova apresentação
    public function store(Request $request)
    {
        $semestreAtual = SemestreService::getCurrent();

        $request->validate([
            'nome' => 'required|string|max:255',
            'materia_id' => 'required|exists:ant_materias,id',
            'peso_apresentacao_id' => 'required|exists:ant_pesos,id',
            'peso_participacao_id' => 'required|exists:ant_pesos,id',
            'descricao' => 'nullable|string',
        ]);

        if (! $this->podeAcessarMateria($request->materia_id, $semestreAtual)) {
            abort(403, 'Você não tem permissão para esta disciplina.');
        }

        // Os pesos devem pertencer à matéria selecionada
        $pesosValidos = AntPeso::where('materia_id', $request->materia_id)
            ->where('semestre', $semestreAtual)
            ->whereIn('id', [$request->peso_apresentacao_id, $request->peso_participacao_id])
            ->count();

        if ($pesosValidos !== 2) {
            return back()->withErrors(['peso_apresentacao_id' => 'Os pesos selecionados devem pertencer à matéria.'])->withInput();
        }

        $apresentacao = AntApresentacao::create([
            'semestre' => $semestreAtual,
            'materia_id' => $request->materia_id,
            'nome' => $request->nome,
            'descricao' => $request->descricao,
            'peso_apresentacao_id' => $request->peso_apresentacao_id,
            'peso_participacao_id' => $request->peso_participacao_id,
        ]);

        return redirect()->route('ant.professor.apresentacoes.show', $apresentacao->id)
            ->with('success', 'Apresentação criada! Agora monte o calendário de apresentações.');
    }

    // Gerenciar uma apresentação (calendário, avaliação, estrelas, rank)
    public function show($id)
    {
        $apresentacao = AntApresentacao::with([
            'materia',
            'agendamentos' => fn ($q) => $q->orderBy('data'),
            'agendamentos.apresentadores.aluno',
            'agendamentos.apresentadores.entrega',
            'agendamentos.estrelas',
        ])->findOrFail($id);

        if (! $this->podeAcessarMateria($apresentacao->materia_id, $apresentacao->semestre)) {
            abort(403, 'Acesso negado a esta disciplina.');
        }

        // Alunos matriculados na matéria (para selecionar apresentadores e estrelas)
        $alunos = AntAluno::whereHas('materias', function ($q) use ($apresentacao) {
            $q->where('ant_materias.id', $apresentacao->materia_id)
                ->where('ant_aluno_materia.semestre', $apresentacao->semestre);
        })->orderBy('nome')->get();

        // Rank de estrelas da matéria no semestre
        $rank = AntEstrela::rankPorMateria($apresentacao->materia_id, $apresentacao->semestre);

        return view('ANT::professores.apresentacoes.show', compact('apresentacao', 'alunos', 'rank'));
    }

    // Adicionar agendamento (data + tema + apresentadores)
    public function storeAgendamento(Request $request, $id)
    {
        $apresentacao = AntApresentacao::findOrFail($id);

        if (! $this->podeAcessarMateria($apresentacao->materia_id, $apresentacao->semestre)) {
            abort(403, 'Acesso negado.');
        }

        $request->validate([
            'data' => 'required|date',
            'tema' => 'required|string|max:255',
            'apresentadores' => 'required|array|min:1',
            'apresentadores.*' => 'exists:ant_alunos,ra',
        ]);

        $agendamento = AntApresentacaoAgendamento::create([
            'apresentacao_id' => $apresentacao->id,
            'data' => $request->data,
            'tema' => $request->tema,
        ]);

        foreach (array_unique($request->apresentadores) as $ra) {
            AntApresentacaoApresentador::create([
                'agendamento_id' => $agendamento->id,
                'aluno_ra' => $ra,
            ]);
        }

        return redirect()->route('ant.professor.apresentacoes.show', $apresentacao->id)
            ->with('success', 'Agendamento de apresentação adicionado!');
    }

    // Avaliar apresentadores (notas) de um agendamento
    public function avaliar(Request $request, $id, $agendamentoId)
    {
        $apresentacao = AntApresentacao::findOrFail($id);
        $agendamento = AntApresentacaoAgendamento::findOrFail($agendamentoId);

        if (! $this->podeAcessarMateria($apresentacao->materia_id, $apresentacao->semestre)) {
            abort(403, 'Acesso negado.');
        }

        $notas = (array) $request->input('notas', []);

        foreach ($agendamento->apresentadores as $apresentador) {
            if (array_key_exists($apresentador->aluno_ra, $notas)) {
                $valor = $notas[$apresentador->aluno_ra];
                $apresentador->update([
                    'nota' => $valor === '' || $valor === null ? null : (float) $valor,
                    'comentario' => $request->input("comentarios.{$apresentador->aluno_ra}"),
                ]);
            }
        }

        return redirect()->route('ant.professor.apresentacoes.show', $apresentacao->id)
            ->with('success', 'Avaliação da apresentação salva!');
    }

    // Salvar os 3 alunos destaques (estrelas) de um agendamento
    public function salvarEstrelas(Request $request, $id, $agendamentoId)
    {
        $apresentacao = AntApresentacao::findOrFail($id);
        $agendamento = AntApresentacaoAgendamento::findOrFail($agendamentoId);

        if (! $this->podeAcessarMateria($apresentacao->materia_id, $apresentacao->semestre)) {
            abort(403, 'Acesso negado.');
        }

        $request->validate([
            'estrelas' => 'required|array|min:1|max:3',
            'estrelas.*' => 'exists:ant_alunos,ra',
        ]);

        // Substitui as estrelas anteriores deste agendamento
        AntEstrela::where('agendamento_id', $agendamento->id)->delete();

        foreach (array_unique($request->estrelas) as $ra) {
            AntEstrela::create([
                'semestre' => $apresentacao->semestre,
                'materia_id' => $apresentacao->materia_id,
                'aluno_ra' => $ra,
                'apresentacao_id' => $apresentacao->id,
                'agendamento_id' => $agendamento->id,
            ]);
        }

        return redirect()->route('ant.professor.apresentacoes.show', $apresentacao->id)
            ->with('success', 'Alunos destaques (estrelas) salvos!');
    }

    // Rank de estrelas da matéria no semestre
    public function rank($materiaId)
    {
        $user = auth()->user();
        $semestreAtual = SemestreService::getForUser($user);

        if (! $this->podeAcessarMateria($materiaId, $semestreAtual)) {
            abort(403, 'Acesso negado.');
        }

        $materia = AntMateria::findOrFail($materiaId);
        $rank = AntEstrela::rankPorMateria($materiaId, $semestreAtual);

        return view('ANT::professores.apresentacoes.rank', compact('materia', 'semestreAtual', 'rank'));
    }
}
