<?php

namespace App\Modules\Bingo\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bingo\Models\BingoCartela;
use App\Modules\Bingo\Models\BingoJogador;
use App\Modules\Bingo\Models\BingoPartida;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BingoController extends Controller
{
    private const TEMAS_PATH = '/Modules/Bingo/temas/';

    public function index(): View
    {
        return view('Bingo::index');
    }

    public function create(): View
    {
        $temas = glob(app_path(self::TEMAS_PATH.'*.png'));
        $temas = array_map(function ($path) {
            return basename($path);
        }, $temas);

        return view('Bingo::create', compact('temas'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tema' => 'required|string',
            'modo_gestor' => 'boolean',
        ]);

        $codigo = $this->gerarCodigo();
        $donoToken = bin2hex(random_bytes(16));

        $partida = BingoPartida::create([
            'codigo' => $codigo,
            'tema' => $validated['tema'],
            'status' => 'espera',
            'modo_gestor' => $validated['modo_gestor'] ?? false,
            'dono_token' => $donoToken,
            'user_id' => $request->user()?->id,
            'numeros_sorteados' => [],
        ]);

        $response = [
            'codigo' => $codigo,
            'dono_token' => $donoToken,
            'url' => config('app.url').'/bingo/'.$codigo,
        ];

        if (! ($validated['modo_gestor'] ?? false)) {
            $jogador = BingoJogador::create([
                'partida_id' => $partida->id,
                'nome' => 'Anfitrião',
                'token' => $donoToken,
                'user_id' => $request->user()?->id,
            ]);

            $cartela = BingoCartela::create([
                'jogador_id' => $jogador->id,
                'numeros' => BingoCartela::gerar(),
                'marcacoes' => [],
            ]);

            $response['jogador_id'] = $jogador->id;
            $response['cartela'] = $cartela->numeros;
        }

        return response()->json($response);
    }

    public function show(string $codigo): View
    {
        $partida = BingoPartida::where('codigo', $codigo)->firstOrFail();
        $joinUrl = config('app.url').'/bingo/'.$codigo;
        $temaUrl = route('bingo.temas', ['tema' => $partida->tema]);

        return view('Bingo::jogo', compact('partida', 'joinUrl', 'temaUrl'));
    }

    public function join(Request $request, string $codigo): JsonResponse
    {
        $partida = BingoPartida::where('codigo', $codigo)->firstOrFail();

        if ($partida->status !== 'espera') {
            return response()->json(['error' => 'Partida já iniciou'], 422);
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:50',
        ]);

        $token = bin2hex(random_bytes(16));

        $jogador = BingoJogador::create([
            'partida_id' => $partida->id,
            'nome' => $validated['nome'],
            'token' => $token,
            'user_id' => $request->user()?->id,
        ]);

        $cartela = BingoCartela::create([
            'jogador_id' => $jogador->id,
            'numeros' => BingoCartela::gerar(),
            'marcacoes' => [],
        ]);

        return response()->json([
            'token' => $token,
            'jogador_id' => $jogador->id,
            'cartela' => $cartela->numeros,
            'cartela_id' => $cartela->id,
        ]);
    }

    public function trocarCartela(Request $request, string $codigo): JsonResponse
    {
        $partida = BingoPartida::where('codigo', $codigo)->firstOrFail();

        if ($partida->status !== 'espera') {
            return response()->json(['error' => 'Partida já iniciou'], 422);
        }

        $token = $request->header('X-Bingo-Token') ?? $request->input('token');

        if (! $token) {
            return response()->json(['error' => 'Token não informado'], 401);
        }

        $jogador = BingoJogador::where('partida_id', $partida->id)
            ->where('token', $token)
            ->firstOrFail();

        $cartela = $jogador->cartela;
        $cartela->update([
            'numeros' => BingoCartela::gerar(),
            'marcacoes' => [],
        ]);

        return response()->json([
            'cartela' => $cartela->fresh()->numeros,
        ]);
    }

    public function iniciar(Request $request, string $codigo): JsonResponse
    {
        $partida = BingoPartida::where('codigo', $codigo)->firstOrFail();

        $token = $request->header('X-Bingo-Token') ?? $request->input('token');

        if (! $token || $token !== $partida->dono_token) {
            return response()->json(['error' => 'Apenas o anfitrião pode iniciar'], 403);
        }

        if ($partida->status !== 'espera') {
            return response()->json(['error' => 'Partida já foi iniciada'], 422);
        }

        $partida->update(['status' => 'jogando']);

        return response()->json(['status' => 'jogando']);
    }

    public function sortear(Request $request, string $codigo): JsonResponse
    {
        $partida = BingoPartida::where('codigo', $codigo)->firstOrFail();

        $token = $request->header('X-Bingo-Token') ?? $request->input('token');

        if (! $token || $token !== $partida->dono_token) {
            return response()->json(['error' => 'Apenas o anfitrião pode sortear'], 403);
        }

        if ($partida->status !== 'jogando') {
            return response()->json(['error' => 'Partida não está em andamento'], 422);
        }

        $numero = $partida->sortearNumero();

        if ($numero === null) {
            return response()->json(['error' => 'Todos os números já foram sorteados'], 422);
        }

        return response()->json([
            'numero' => $numero,
            'numeros_sorteados' => $partida->fresh()->numeros_sorteados,
        ]);
    }

    public function marcar(Request $request, string $codigo): JsonResponse
    {
        $partida = BingoPartida::where('codigo', $codigo)->firstOrFail();

        if ($partida->status !== 'jogando') {
            return response()->json(['error' => 'Partida não está em andamento'], 422);
        }

        $token = $request->header('X-Bingo-Token') ?? $request->input('token');

        if (! $token) {
            return response()->json(['error' => 'Token não informado'], 401);
        }

        $jogador = BingoJogador::where('partida_id', $partida->id)
            ->where('token', $token)
            ->firstOrFail();

        $cartela = $jogador->cartela;

        $validated = $request->validate([
            'linha' => 'required|integer|min:0|max:2',
            'coluna' => 'required|integer|min:0|max:2',
        ]);

        $marcacoes = $cartela->marcacoes ?? [];
        $pos = $validated['linha'].'-'.$validated['coluna'];

        if (in_array($pos, $marcacoes)) {
            $marcacoes = array_values(array_filter($marcacoes, fn ($m) => $m !== $pos));
        } else {
            $marcacoes[] = $pos;
        }

        $cartela->update(['marcacoes' => $marcacoes]);

        return response()->json([
            'marcacoes' => $marcacoes,
        ]);
    }

    public function declararBingo(Request $request, string $codigo): JsonResponse
    {
        $partida = BingoPartida::where('codigo', $codigo)->firstOrFail();

        $token = $request->header('X-Bingo-Token') ?? $request->input('token');

        if (! $token) {
            return response()->json(['error' => 'Token não informado'], 401);
        }

        $jogador = BingoJogador::where('partida_id', $partida->id)
            ->where('token', $token)
            ->firstOrFail();

        $cartela = $jogador->cartela;

        if (! $cartela->verificarBingo()) {
            return response()->json(['error' => 'Cartela não completou bingo'], 422);
        }

        $jogador->update(['bingo_feito' => true]);
        $partida->update(['status' => 'finalizada']);

        return response()->json([
            'vencedor' => $jogador->nome,
            'status' => 'finalizada',
        ]);
    }

    public function estado(string $codigo): JsonResponse
    {
        $partida = BingoPartida::where('codigo', $codigo)->firstOrFail();
        $temaUrl = route('bingo.temas', ['tema' => $partida->tema]);

        $jogadores = BingoJogador::where('partida_id', $partida->id)
            ->get(['id', 'nome', 'bingo_feito']);

        $data = [
            'partida' => [
                'codigo' => $partida->codigo,
                'tema' => $partida->tema,
                'status' => $partida->status,
                'modo_gestor' => $partida->modo_gestor,
                'numeros_sorteados' => $partida->numeros_sorteados ?? [],
            ],
            'jogadores' => $jogadores,
            'tema_url' => $temaUrl,
        ];

        $token = request()->header('X-Bingo-Token') ?? request()->input('token');

        if ($token) {
            $jogador = BingoJogador::where('partida_id', $partida->id)
                ->where('token', $token)
                ->with('cartela')
                ->first();

            if ($jogador) {
                $data['meu_jogador'] = [
                    'id' => $jogador->id,
                    'nome' => $jogador->nome,
                    'bingo_feito' => $jogador->bingo_feito,
                    'cartela' => [
                        'id' => $jogador->cartela->id,
                        'numeros' => $jogador->cartela->numeros,
                        'marcacoes' => $jogador->cartela->marcacoes ?? [],
                    ],
                ];
            }

            $data['e_dono'] = $token === $partida->dono_token;
        }

        return response()->json($data);
    }

    public function historico(Request $request): View
    {
        $partidas = BingoPartida::where('user_id', $request->user()->id)
            ->withCount('jogadores')
            ->latest()
            ->paginate(20);

        return view('Bingo::historico', compact('partidas'));
    }

    public function temaImagem(string $tema)
    {
        $path = app_path(self::TEMAS_PATH.basename($tema));

        if (! file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Cache-Control' => 'public, max_age=86400',
            'Content-Type' => mime_content_type($path),
        ]);
    }

    private function gerarCodigo(): string
    {
        $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $codigo = '';
            for ($i = 0; $i < 6; $i++) {
                $codigo .= $caracteres[random_int(0, strlen($caracteres) - 1)];
            }
        } while (BingoPartida::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
