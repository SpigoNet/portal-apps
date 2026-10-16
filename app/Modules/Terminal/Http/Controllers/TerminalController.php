<?php

namespace App\Modules\Terminal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Terminal\Models\Conquista;
use App\Modules\Terminal\Models\Diario;
use App\Modules\Terminal\Models\Resultado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TerminalController extends Controller
{
    private const TAMANHO_RANKING = 10;

    /** Códigos de conquista aceitos (nome e descrição ficam no cliente). */
    public const CONQUISTAS = [
        'BOOT', 'ZERO_WASTE', 'PLENO', 'MARATONISTA', 'TECLADISTA', 'PONTUAL', 'HABITUE', 'VETERANO', 'HACKER',
    ];

    public function index(): View
    {
        return view('Terminal::index', ['hoje' => Carbon::today()->toDateString()]);
    }

    /**
     * Guarda o tempo de uma fase concluída; só o melhor tempo de cada usuário permanece.
     */
    public function registrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'modo' => ['required', 'in:normal,hard'],
            'fase' => ['required', 'integer', 'min:1', 'max:9999'],
            'tempo_ms' => ['required', 'integer', 'min:500', 'max:86400000'],
            'estrelas' => ['nullable', 'integer', 'between:1,3'],
        ]);

        $resultado = Resultado::firstOrNew(['user_id' => $request->user()->id, 'modo' => $dados['modo'], 'fase' => $dados['fase']]);
        $recorde = ! $resultado->exists || $dados['tempo_ms'] < $resultado->tempo_ms;
        $estrelas = max((int) $resultado->estrelas, (int) ($dados['estrelas'] ?? 0));

        if ($recorde || $estrelas !== (int) $resultado->estrelas) {
            if ($recorde) {
                $resultado->tempo_ms = $dados['tempo_ms'];
            }
            $resultado->estrelas = $estrelas;
            $resultado->save();
        }

        return response()->json(['recorde' => $recorde, 'tempo_ms' => $resultado->tempo_ms, 'estrelas' => $estrelas]);
    }

    /**
     * Desafio diário: ranking do dia (por modo), melhor tempo do usuário e sequência de dias seguidos.
     */
    public function diario(Request $request): JsonResponse
    {
        $modo = $request->query('modo') === 'hard' ? 'hard' : 'normal';
        $hoje = Carbon::today()->toDateString();
        $eu = $request->user()->id;

        $ranking = Diario::query()
            ->join('users', 'users.id', '=', 'terminal_diarios.user_id')
            ->where('modo', $modo)->whereDate('data', $hoje)
            ->orderBy('tempo_ms')->orderBy('terminal_diarios.id')
            ->limit(self::TAMANHO_RANKING)
            ->get(['users.name', 'terminal_diarios.user_id', 'terminal_diarios.tempo_ms'])
            ->map(fn ($r) => ['nome' => $r->name, 'tempo_ms' => $r->tempo_ms, 'eu' => $r->user_id === $eu])
            ->all();

        $meu = Diario::where('user_id', $eu)->where('modo', $modo)->whereDate('data', $hoje)->value('tempo_ms');

        return response()->json([
            'data' => $hoje,
            'modo' => $modo,
            'ranking' => $ranking,
            'meu_tempo_ms' => $meu === null ? null : (int) $meu,
            'sequencia' => $this->sequencia($eu),
        ]);
    }

    /**
     * Guarda o tempo do desafio de hoje; só o melhor tempo do dia permanece.
     */
    public function registrarDiario(Request $request): JsonResponse
    {
        $hoje = Carbon::today()->toDateString();
        $dados = $request->validate([
            'modo' => ['required', 'in:normal,hard'],
            'data' => ['required', 'in:'.$hoje],
            'tempo_ms' => ['required', 'integer', 'min:500', 'max:86400000'],
        ]);

        $registro = Diario::firstOrNew(['user_id' => $request->user()->id, 'modo' => $dados['modo'], 'data' => $hoje]);
        $recorde = ! $registro->exists || $dados['tempo_ms'] < $registro->tempo_ms;

        if ($recorde) {
            $registro->tempo_ms = $dados['tempo_ms'];
            $registro->save();
        }

        return response()->json([
            'recorde' => $recorde,
            'tempo_ms' => $registro->tempo_ms,
            'sequencia' => $this->sequencia($request->user()->id),
        ]);
    }

    /**
     * Libera uma conquista (idempotente). Retorna `novo` = true só na primeira vez.
     */
    public function registrarConquista(Request $request): JsonResponse
    {
        $dados = $request->validate(['codigo' => ['required', 'in:'.implode(',', self::CONQUISTAS)]]);

        $conquista = Conquista::firstOrCreate(['user_id' => $request->user()->id, 'codigo' => $dados['codigo']]);

        return response()->json(['novo' => $conquista->wasRecentlyCreated]);
    }

    /**
     * Dias seguidos com o desafio concluído (qualquer modo). Vale também se hoje ainda não foi jogado, mas ontem sim.
     */
    private function sequencia(int $userId): int
    {
        $dias = Diario::where('user_id', $userId)->pluck('data')
            ->map(fn ($d) => substr((string) $d, 0, 10))->unique()->flip();

        $dia = Carbon::today();
        if (! $dias->has($dia->toDateString())) {
            $dia = $dia->subDay();
        }

        $total = 0;
        while ($dias->has($dia->toDateString())) {
            $total++;
            $dia = $dia->subDay();
        }

        return $total;
    }

    /**
     * Rankings do "Funcionário do mês" (separados por modo): menor tempo por fase, menor tempo médio no geral
     * e quem concluiu mais fases (desempate pelo tempo total).
     */
    public function ranking(Request $request): JsonResponse
    {
        $eu = $request->user()->id;
        $modo = $request->query('modo') === 'hard' ? 'hard' : 'normal';
        $faseMaxima = (int) Resultado::where('modo', $modo)->max('fase');
        $fase = min(max((int) $request->query('fase', 1), 1), max($faseMaxima, 1));

        $porFase = Resultado::query()
            ->join('users', 'users.id', '=', 'terminal_resultados.user_id')
            ->where('modo', $modo)->where('fase', $fase)
            ->orderBy('tempo_ms')->orderBy('terminal_resultados.id')
            ->limit(self::TAMANHO_RANKING)
            ->get(['users.name', 'terminal_resultados.user_id', 'terminal_resultados.tempo_ms'])
            ->map(fn ($r) => ['nome' => $r->name, 'tempo_ms' => $r->tempo_ms, 'eu' => $r->user_id === $eu])
            ->all();

        $agregado = fn () => Resultado::query()
            ->where('modo', $modo)
            ->join('users', 'users.id', '=', 'terminal_resultados.user_id')
            ->groupBy('terminal_resultados.user_id', 'users.name')
            ->select([
                'terminal_resultados.user_id',
                'users.name',
                DB::raw('count(*) as fases'),
                DB::raw('sum(tempo_ms) as total_ms'),
            ])
            ->limit(self::TAMANHO_RANKING);

        $geral = $agregado()
            ->orderByRaw('sum(tempo_ms) * 1.0 / count(*)')->orderBy('terminal_resultados.user_id')
            ->get()
            ->map(fn ($r) => [
                'nome' => $r->name,
                'fases' => (int) $r->fases,
                'tempo_ms' => (int) round($r->total_ms / $r->fases),
                'eu' => (int) $r->user_id === $eu,
            ])->all();

        $solucionadores = $agregado()
            ->orderByRaw('count(*) desc')->orderByRaw('sum(tempo_ms)')->orderBy('terminal_resultados.user_id')
            ->get()
            ->map(fn ($r) => [
                'nome' => $r->name,
                'fases' => (int) $r->fases,
                'tempo_ms' => (int) $r->total_ms,
                'eu' => (int) $r->user_id === $eu,
            ])->all();

        return response()->json([
            'modo' => $modo,
            'fase' => $fase,
            'estrelas' => Resultado::where('user_id', $eu)->where('modo', $modo)->pluck('estrelas', 'fase')->all() ?: new \stdClass,
            'conquistas' => Conquista::where('user_id', $eu)->pluck('codigo')->all(),
            'fase_maxima' => max($faseMaxima, 1),
            'por_fase' => $porFase,
            'geral' => $geral,
            'solucionadores' => $solucionadores,
        ]);
    }
}
