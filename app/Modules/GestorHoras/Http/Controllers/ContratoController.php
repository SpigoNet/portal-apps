<?php

namespace App\Modules\GestorHoras\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestorHoras\Models\Contrato;
use App\Modules\GestorHoras\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ContratoController extends Controller
{
    /**
     * Lista os contratos (Dashboard Principal).
     */
    public function index()
    {
        Gate::authorize('gh.acessar');

        $user = auth()->user();
        $query = Contrato::query()->with('cliente');

        // Filtro por Cliente
        if ($user->gh_role === 'client') {
            if (!$user->gh_cliente_id) abort(403);
            $query->where('gh_cliente_id', $user->gh_cliente_id);
        }

        // Recupera todos ordenados
        $todosContratos = $query->orderBy('created_at', 'desc')->get();

        // Separa em duas coleções
        $ativos = $todosContratos->where('status', 'ativo');
        $inativos = $todosContratos->whereIn('status', ['finalizado', 'cancelado']);

        return view('GestorHoras::index', compact('ativos', 'inativos'));
    }

    /**
     * Exibe formulário de criação de contrato.
     */
    public function create()
    {
        // Apenas Admin e Dev podem criar contratos
        Gate::authorize('gh.operacional');

        $clientes = Cliente::all(); // Precisa listar clientes para selecionar
        return view('GestorHoras::create', compact('clientes'));
    }

    /**
     * Salva novo contrato.
     */
    public function store(Request $request)
    {
        Gate::authorize('gh.operacional');

        $validated = $request->validate([
            'gh_cliente_id' => 'required|exists:gh_clientes,id',
            'titulo' => 'required|string|max:255',
            'tipo' => 'required|in:fixo,recorrente',
            'horas_contratadas' => 'required|numeric|min:0', // Valor Mensal (se recorrente) ou Total (se fixo)
            'data_inicio' => 'required|date',
            'data_fim' => 'nullable|date|after:data_inicio',
        ]);

        // Cria o Contrato "Pai"
        $contrato = Contrato::create($validated);

        // --- AUTOMATIZAÇÃO PARA RECORRENTE ---
        if ($contrato->tipo === 'recorrente') {

            // Se não tiver data fim, define padrão de 12 meses
            $inicio = \Carbon\Carbon::parse($contrato->data_inicio);
            $fim = $contrato->data_fim
                ? \Carbon\Carbon::parse($contrato->data_fim)
                : $inicio->copy()->addMonths(12);

            // Loop mês a mês criando os itens
            $atual = $inicio->copy();

            while ($atual->lte($fim)) { // Enquanto data atual <= data fim

                // Formata o título: "Janeiro/2024"
                $mesAno = ucfirst($atual->translatedFormat('F/Y'));

                $contrato->itens()->create([
                    'titulo' => $mesAno,
                    'descricao' => "Pacote de horas referente a {$mesAno}",
                    'horas_estimadas' => $validated['horas_contratadas'], // Usa o valor mensal
                    'data_referencia' => $atual->format('Y-m-d'),
                ]);

                $atual->addMonth(); // Avança 1 mês
            }
        }

        return redirect()->route('gestor-horas.index')
            ->with('success', 'Contrato criado e meses gerados com sucesso!');
    }

    public function show($id)
    {
        Gate::authorize('gh.acessar');

        // Carregamos:
        // 1. O cliente
        // 2. Os itens E os apontamentos dentro deles (ordenados)
        // 3. Os apontamentos gerais do contrato
        $contrato = Contrato::with([
            'cliente',
            'itens' => function($q) {
                // Ordena primeiro por data_referencia (para os mensais), depois por ID
                $q->orderBy('data_referencia', 'asc')->orderBy('id', 'asc');
            },
            'itens.apontamentos' => function($q) {
                $q->orderBy('data_realizacao', 'desc');
            },
            'apontamentos' => function($q) {
                $q->orderBy('data_realizacao', 'desc');
            }
        ])->findOrFail($id);

        // Segurança...
        if (auth()->user()->gh_role === 'client' && auth()->user()->gh_cliente_id !== $contrato->gh_cliente_id) {
            abort(403);
        }

        return view('GestorHoras::show', compact('contrato'));
    }

    public function publicView($token)
    {
        // Busca o cliente pelo token
        $cliente = Cliente::where('access_token', $token)->firstOrFail();

        // Carrega os contratos desse cliente específico
        $contratos = Contrato::with([
                'itens',
                'apontamentos' => function($q) {
                    $q->orderBy('data_realizacao', 'desc');
                },
                'itens' => function($q) {
                    // Ordena primeiro por data_referencia (para os mensais), depois por ID
                    $q->orderBy('data_referencia', 'asc')->orderBy('id', 'asc');
                },
            ])
            ->where('gh_cliente_id', $cliente->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Retorna uma view específica "Clean" (sem menus de admin)
        return view('GestorHoras::public_dashboard', compact('cliente', 'contratos'));
    }
}
