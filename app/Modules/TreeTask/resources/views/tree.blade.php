@php
    use App\Modules\TreeTask\Support\Aparencia;
@endphp
<x-TreeTask::layout :titulo="$projeto->nome" ativo="projetos">
    @include('TreeTask::partials.projeto-cabecalho', ['aba' => 'arvore'])

    <div class="tt-arvore-box">
        <ul class="tt-arvore">
            <li>
                <div class="tt-no raiz">
                    {{ $projeto->nome }}
                    <small>Objetivo principal</small>
                </div>

                @if($projeto->fases->count() > 0)
                    <ul>
                        @foreach($projeto->fases as $fase)
                            <li>
                                <div class="tt-no fase">
                                    {{ $fase->nome }}
                                    <small>Etapa · {{ $fase->tarefas->count() }} {{ $fase->tarefas->count() === 1 ? 'tarefa' : 'tarefas' }}</small>
                                </div>

                                @if($fase->tarefas->count() > 0)
                                    <ul>
                                        @foreach($fase->tarefas as $tarefa)
                                            <li>
                                                <a href="{{ route('treetask.tarefas.show', $tarefa->id_tarefa) }}" class="tt-no tarefa {{ Aparencia::statusClasse($tarefa->status) }}">
                                                    {{ \Illuminate\Support\Str::limit($tarefa->titulo, 28) }}
                                                    <small>{{ $tarefa->status }} · {{ $tarefa->responsavel ? \Illuminate\Support\Str::words($tarefa->responsavel->name, 1, '') : 'sem responsável' }}</small>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        </ul>
    </div>
</x-TreeTask::layout>
