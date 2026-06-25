<?php

namespace App\Modules\Alfred\Http\Controllers;

use App\Modules\Alfred\Models\Medicamento;
use App\Modules\Alfred\Models\Persona;
use App\Modules\Alfred\Models\RegistroMedicamento;
use App\Modules\Alfred\Services\EvolutionApiService;
use Illuminate\Http\Request;

class MedicamentoController
{
    public function index()
    {
        $medicamentos = Medicamento::all();
        $alertas = Medicamento::baixoEstoque()->get();

        $medicamentosTomadosHoje = RegistroMedicamento::medicamentosTomadosHoje()
            ->pluck('medicamento_id')
            ->toArray();

        return view('Alfred::medicamentos.index', compact('medicamentos', 'alertas', 'medicamentosTomadosHoje'));
    }

    public function create()
    {
        return view('Alfred::medicamentos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'estoque_atual' => 'required|integer|min:0',
            'ponto_recompra' => 'required|integer|min:1',
        ]);

        $validated['user_id'] = auth()->id();

        Medicamento::create($validated);

        return redirect()->route('alfred.medicamentos.index')
            ->with('success', 'Medicamento cadastrado com sucesso!');
    }

    public function edit(Medicamento $medicamento)
    {
        return view('Alfred::medicamentos.edit', compact('medicamento'));
    }

    public function update(Request $request, Medicamento $medicamento)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'estoque_atual' => 'required|integer|min:0',
            'ponto_recompra' => 'required|integer|min:1',
        ]);

        $medicamento->update($validated);

        return redirect()->route('alfred.medicamentos.index')
            ->with('success', 'Medicamento atualizado com sucesso!');
    }

    public function destroy(Medicamento $medicamento)
    {
        $medicamento->delete();

        return redirect()->route('alfred.medicamentos.index')
            ->with('success', 'Medicamento removido com sucesso!');
    }

    public function tomar(Medicamento $medicamento, Request $request)
    {
        $data = $request->input('data');

        if (RegistroMedicamento::foiTomadoNaData($medicamento->id, $data ?? now()->toDateString())) {
            return redirect()->back()->with('warning', 'Este medicamento já foi registrado para esta data!');
        }

        RegistroMedicamento::registrar($medicamento->id, 1, $data);

        $estoqueAnterior = $medicamento->estoque_atual;
        $medicamento->tomarDose();
        $estoqueAtual = $medicamento->estoque_atual;

        $mensagem = 'Medicamento registrado';
        if ($data) {
            $mensagem .= ' para '.\Carbon\Carbon::parse($data)->format('d/m/Y');
        }
        $mensagem .= '!';

        if ($estoqueAtual <= $medicamento->ponto_recompra && $estoqueAnterior > $medicamento->ponto_recompra) {
            $mensagem .= ' ⚠️ Estoque baixo! Comprar mais '.$medicamento->nome;
        }

        if ($estoqueAtual <= 0) {
            $mensagem .= ' 🚨 ESTOQUE ZERO! Comprar urgente!';
        }

        try {
            $chopper = Persona::where('slug', 'chopper')->where('active', true)->first();
            if ($chopper && $chopper->whatsapp_group_jid) {
                $evo = new EvolutionApiService;
                $greeting = $chopper->personality['greetings'][0] ?? 'Oi!';
                $text = $greeting."\n";
                $text .= "Registro: tomou o medicamento {$medicamento->nome}.";
                $evo->sendTextToGroup($chopper->whatsapp_group_jid, $text);
            }
        } catch (\Throwable $e) {
            //
        }

        return redirect()->back()->with('success', $mensagem);
    }

    public function desfazer(Medicamento $medicamento, Request $request)
    {
        $data = $request->input('data');
        $dataQuery = $data ?? now()->toDateString();

        if (RegistroMedicamento::foiTomadoNaData($medicamento->id, $dataQuery)) {
            RegistroMedicamento::desfazerRegistroNaData($medicamento->id, $dataQuery);

            $medicamento->estoque_atual = $medicamento->estoque_atual + 1;
            $medicamento->save();

            $mensagem = 'Registro desfeito';
            if ($data) {
                $mensagem .= ' para '.\Carbon\Carbon::parse($data)->format('d/m/Y');
            }
            $mensagem .= '! O estoque foi restaurado.';

            return redirect()->back()->with('success', $mensagem);
        }

        return redirect()->back()->with('warning', 'Não há registro para desfazer nesta data.');
    }
}
