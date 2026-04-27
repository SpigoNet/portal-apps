<?php

namespace App\Modules\GestorHoras\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\GestorHoras\Models\Contrato;
use App\Modules\GestorHoras\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Modules\GestorHoras\Models\ContratoItem;
use App\Modules\GestorHoras\Models\LogAceite;
use Illuminate\Support\Str;

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
            'tipo' => 'required|in:fixo,recorrente,livre',
            'horas_contratadas' => 'required|numeric|min:0', // Valor Mensal (se recorrente) ou Total (se fixo)
            'valor_hora' => 'required|numeric|min:0',
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

        $mensagem = $contrato->tipo === 'recorrente'
            ? 'Contrato criado e meses gerados com sucesso!'
            : 'Contrato criado com sucesso!';

        return redirect()->route('gestor-horas.index')
            ->with('success', $mensagem);
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

    public function homologarItem(Request $request, $id)
    {
        // Permite que clientes autenticados ou operadores realizem a homologação
        Gate::authorize('gh.acessar');

        $item = ContratoItem::with(['apontamentos', 'contrato'])->findOrFail($id);

        // Impede homologação se já homologado
        if ($item->homologado) {
            return back()->withErrors(['erro' => 'Este item já foi homologado.']);
        }

        // Gera snapshot do conteúdo relevante
        $snapshot = [
            'item' => [
                'id' => $item->id,
                'titulo' => $item->titulo,
                'descricao' => $item->descricao,
                'horas_estimadas' => $item->horas_estimadas,
                'data_referencia' => optional($item->data_referencia)->toDateString(),
            ],
            'apontamentos' => $item->apontamentos->map(function($a){
                return [
                    'id' => $a->id,
                    'user_id' => $a->user_id,
                    'descricao' => $a->descricao,
                    'data_realizacao' => $a->data_realizacao?->toDateString(),
                    'minutos_gastos' => $a->minutos_gastos,
                ];
            })->toArray(),
        ];

        $snapshotJson = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
        $hash = hash('sha256', $snapshotJson);

        // Cria log de aceite
        $log = LogAceite::create([
            'gh_contrato_id' => $item->gh_contrato_id ?? $item->contrato->id ?? null,
            'gh_contrato_item_id' => $item->id,
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'snapshot_hash' => $hash,
            'snapshot_json' => $snapshotJson,
        ]);

        // Marca item como homologado
        $item->update([
            'homologado' => true,
            'homologado_por' => auth()->id(),
            'homologado_em' => now(),
            'homologado_hash' => $hash,
        ]);

        // Envia e-mail de notificação
        $titulo = sprintf('[ACEITE DIGITAL] - Item Homologado - Spigo.Net - %s', $item->titulo);
        $corpo = "Item: {$item->titulo}\nContrato: {$item->contrato->titulo}\nAceito por: " . auth()->user()->name . " (ID: " . auth()->id() . ")\nData: " . now()->toDateTimeString() . "\nHash: {$hash}\n\nResumo:\n" . strip_tags($item->descricao);

        // Destinatários: gustavo@spigo.net e financeiro (ajustar e-mail de financeiro conforme necessário)
        $emails = ['gustavo@spigo.net', 'financeiro@nw.com'];

        foreach ($emails as $to) {
            try {
                Mail::raw($corpo, function ($m) use ($to, $titulo) {
                    $m->to($to)->subject($titulo);
                });
            } catch (\Throwable $e) {
                // Não falha a operação principal se o envio falhar
            }
        }

        return back()->with('success', 'Item homologado com sucesso. O aceite foi registrado e notificado por e-mail.');
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
