@props([
    'rank' => collect(),
    'materia' => null,
    'semestre' => null,
    'currentUserId' => null,
])

@php
    // Medalistas = todos com posição <= 3 (inclui empates no pódio)
    $medalistas = $rank->filter(fn ($i) => $i->posicao <= 3)->values();
    $rest = $rank->filter(fn ($i) => $i->posicao > 3)->values();

    $grupos = $medalistas->groupBy('posicao');

    // Ordem visual do pódio: [2º, 1º, 3º] (esquerda -> centro -> direita)
    $esq = $grupos->get(2, collect());
    $centro = $grupos->get(1, collect());
    $dir = $grupos->get(3, collect());
    $ordem = $esq->values()->concat($centro->values())->concat($dir->values());

    $estilo = [
        1 => ['cor' => 'from-yellow-300 to-amber-500 border-yellow-400', 'altura' => 190, 'icon' => '👑', 'label' => 'Campeão'],
        2 => ['cor' => 'from-slate-200 to-slate-400 border-slate-300', 'altura' => 150, 'icon' => '🥈', 'label' => 'Vice'],
        3 => ['cor' => 'from-orange-200 to-amber-700 border-amber-500', 'altura' => 112, 'icon' => '🥉', 'label' => 'Terceiro'],
    ];

    if (!function_exists('rank_iniciais')) {
        function rank_iniciais($nome) {
            $partes = preg_split('/\s+/', trim($nome));
            $ini = '';
            foreach (array_slice($partes, 0, 2) as $p) {
                $ini .= mb_strtoupper(mb_substr($p, 0, 1));
            }
            return $ini ?: '?';
        }
    }
@endphp

<div x-data="{ montado: false }" x-init="setTimeout(() => montado = true, 50)"
     class="relative overflow-hidden rounded-2xl bg-gradient-to-b from-indigo-50 to-white p-6 sm:p-10">

    {{-- Estrelas flutuantes de fundo --}}
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        @for ($i = 0; $i < 14; $i++)
            <span class="absolute text-amber-400 float-star"
                  style="left: {{ rand(2, 96) }}%; top: {{ rand(2, 70) }}%; animation-delay: {{ ($i * 0.4) }}s; font-size: {{ rand(12, 26) }}px;">
                ★
            </span>
        @endfor
    </div>

    <div class="relative mb-8 text-center">
        <h3 class="text-lg font-extrabold tracking-tight text-gray-800">
            🏆 Ranking de Estrelas
        </h3>
        @if ($materia)
            <p class="text-sm text-gray-500">
                {{ $materia->nome }} @if($semestre) · {{ $semestre }} @endif
            </p>
        @endif
    </div>

    @if ($medalistas->isEmpty())
        <p class="relative text-center text-gray-400">Ainda não há estrelas nesta disciplina. 🌟</p>
    @else
        {{-- Pódio --}}
        <div class="relative flex flex-wrap items-end justify-center gap-3 sm:gap-6 pb-2">

            @foreach ($ordem as $vi => $item)
                @php
                    $m = $estilo[$item->posicao] ?? $estilo[3];
                    $voce = $currentUserId && $item->aluno->user_id === $currentUserId;
                @endphp
                <div class="flex w-20 flex-col items-center sm:w-28"
                     style="animation: pop-in 0.6s {{ ($vi * 0.1) }}s both;">

                    {{-- Avatar + coroa/medalha --}}
                    <div class="relative mb-2">
                        <span class="absolute -top-5 left-1/2 -translate-x-1/2 text-2xl drop-shadow sm:text-3xl">
                            {{ $m['icon'] }}
                        </span>
                        <div class="flex h-12 w-12 items-center justify-center rounded-full border-2 border-white bg-white text-base font-black text-indigo-600 shadow-lg ring-2 ring-indigo-100 sm:h-14 sm:w-14 sm:text-lg">
                            {{ rank_iniciais($item->aluno->nome) }}
                        </div>
                    </div>

                    <div class="mb-1 max-w-full truncate text-center text-[11px] font-semibold text-gray-800 sm:text-xs
                                {{ $voce ? 'rounded-full bg-indigo-100 px-2 py-0.5 text-indigo-700' : '' }}">
                        {{ $item->aluno->nome }}
                        @if ($voce)<span class="ml-1 text-[10px]">(você)</span>@endif
                    </div>

                    {{-- Barra do pódio --}}
                    <div class="group relative flex w-full items-start justify-center rounded-t-xl border-2 {{ $m['cor'] }} shadow-md transition-transform duration-300 hover:-translate-y-1"
                         style="height: {{ $m['altura'] }}px; animation: podium-rise 0.7s {{ ($vi * 0.1) }}s both; padding-top: 10px;">

                        <div class="flex flex-col items-center gap-1 text-gray-900" style="margin-top: 30px;">
                            <span class="text-xl font-black sm:text-2xl">{{ $item->estrelas }} ⭐</span>
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-700">estrelas</span>
                        </div>

                        {{-- Faixa de posição --}}
                        <span class="absolute -bottom-6 left-1/2 -translate-x-1/2 rounded-full bg-white px-3 py-0.5 text-sm font-black text-gray-700 shadow ring-1 ring-gray-200">
                            {{ $item->posicao }}º
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="relative mt-6 px-2">
            <span class="block h-px w-full bg-gradient-to-r from-transparent via-indigo-200 to-transparent"></span>
        </div>
    @endif

    {{-- Lista dos demais --}}
    @if ($rest->isNotEmpty())
        <ul class="relative mt-6 space-y-2">
            @foreach ($rest as $i => $item)
                @php $voce = $currentUserId && $item->aluno->user_id === $currentUserId; @endphp
                <li class="flex items-center gap-3 rounded-xl border border-gray-100 bg-white px-3 py-2 shadow-sm transition hover:shadow-md
                          {{ $voce ? 'ring-2 ring-indigo-200' : '' }}"
                    style="animation: slide-up 0.5s {{ (0.2 + $i * 0.06) }}s both;">

                    <span class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-indigo-50 text-sm font-black text-indigo-600">
                        {{ $item->posicao }}º
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-gray-800">
                            {{ $item->aluno->nome }}
                            @if ($voce)<span class="ml-1 text-[10px] font-bold text-indigo-600">(você)</span>@endif
                        </div>
                        <div class="text-[11px] font-mono text-gray-400">{{ $item->ra }}</div>
                    </div>

                    <div class="flex flex-none items-center gap-1 text-yellow-500">
                        <span class="text-base font-black">{{ $item->estrelas }}</span>
                        <span class="text-lg">⭐</span>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    <p class="relative mt-6 text-center text-xs text-gray-400">
        As estrelas acumulam durante o semestre e, no boletim, a nota de participação é
        normalizada pelo maior número de estrelas da turma.
    </p>
</div>

<style>
    @keyframes podium-rise {
        0%   { transform: translateY(110%); opacity: 0; }
        60%  { transform: translateY(-8%);  opacity: 1; }
        100% { transform: translateY(0); }
    }
    @keyframes pop-in {
        0%   { transform: scale(0.6) translateY(20px); opacity: 0; }
        70%  { transform: scale(1.05); }
        100% { transform: scale(1); opacity: 1; }
    }
    @keyframes slide-up {
        0%   { transform: translateY(12px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    @keyframes float-star {
        0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.5; }
        50%      { transform: translateY(-14px) rotate(12deg); opacity: 1; }
    }
    .float-star { animation: float-star 4s ease-in-out infinite; }
</style>
