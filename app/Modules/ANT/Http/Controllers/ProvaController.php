<?php

namespace App\Modules\ANT\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\ANT\Models\AntAluno;
use App\Modules\ANT\Models\AntTrabalho;
use App\Modules\ANT\Models\AntProvaResposta;

class ProvaController extends Controller
{
    /**
     * Exibe o resultado da prova (Gabarito do Aluno)
     */
    public function resultado($idTrabalho)
    {
        $user = auth()->user();
        $aluno = AntAluno::where('user_id', $user->id)->firstOrFail();

        // 1. Busca o Trabalho e a Prova Vinculada
        $trabalho = AntTrabalho::with(['prova.questoes.alternativas'])
            ->findOrFail($idTrabalho);

        if (!$trabalho->prova) {
            abort(404, 'Este trabalho não possui uma prova vinculada.');
        }

        $prova = $trabalho->prova;

        // 2. Busca as Respostas do Aluno para esta prova
        // Indexamos pelo ID da questão para facilitar o acesso na View ($respostas[questao_id])
        $respostas = AntProvaResposta::where('prova_id', $prova->id)
            ->where('aluno_ra', $aluno->ra)
            ->get()
            ->keyBy('questao_id');

        // 3. Cálculo da Nota Total (Soma das pontuações registradas)
        $notaTotal = $respostas->sum('pontuacao');

        // Nota Máxima possível (Opcional: lógica simplificada, assume que cada questão vale pontos iguais ou baseada no peso)
        // Por enquanto, vamos exibir a nota calculada.

        return view('ANT::provas.resultado', compact('trabalho', 'prova', 'respostas', 'notaTotal', 'aluno'));
    }
}
