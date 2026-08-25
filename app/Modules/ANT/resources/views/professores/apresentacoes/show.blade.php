<x-ANT::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <a href="{{ route('ant.professor.apresentacoes.index') }}" class="text-gray-500 hover:text-gray-900">Apresentações</a>
            <span class="text-gray-400 mx-2">/</span>
            {{ $apresentacao->nome }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Cabeçalho / ações --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-sm text-gray-500">{{ $apresentacao->materia->nome }} · {{ $apresentacao->semestre }}</div>
                    <div class="font-bold text-gray-900 text-lg">{{ $apresentacao->nome }}</div>
                    @if($apresentacao->descricao)
                        <p class="text-sm text-gray-600 mt-1">{{ $apresentacao->descricao }}</p>
                    @endif
                    <div class="text-xs text-gray-400 mt-1">
                        Pesos: Apresentação ({{ $apresentacao->pesoApresentacao->grupo ?? '-' }}) ·
                        Participação ({{ $apresentacao->pesoParticipacao->grupo ?? '-' }})
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('ant.professor.apresentacoes.rank', $apresentacao->materia_id) }}"
                       class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                        ⭐ Rank de Estrelas
                    </a>
                    <a href="{{ route('ant.professor.boletim', $apresentacao->materia_id) }}"
                       class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                        Boletim
                    </a>
                </div>
            </div>

            {{-- Novo agendamento --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-bold text-gray-700 mb-3">+ Adicionar data de apresentação</h3>
                <form method="POST" action="{{ route('ant.professor.apresentacoes.agendamento.store', $apresentacao->id) }}">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Data</label>
                            <input type="date" name="data" required
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tema</label>
                            <input type="text" name="tema" required maxlength="255"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Apresentadores</label>
                            <select name="apresentadores[]" multiple required size="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @foreach($alunos as $aluno)
                                    <option value="{{ $aluno->ra }}">{{ $aluno->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <button type="submit"
                            class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2 rounded-lg text-sm font-semibold">
                        Adicionar agendamento
                    </button>
                </form>
            </div>

            {{-- Agendamentos --}}
            @forelse($apresentacao->agendamentos as $agendamento)
                @php
                    $estrelasAtuais = $agendamento->estrelas->pluck('aluno_ra')->all();
                @endphp
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3 mb-3">
                        <div>
                            <span class="font-bold text-gray-900">{{ \Carbon\Carbon::parse($agendamento->data)->format('d/m/Y') }}</span>
                            <span class="ml-2 text-gray-600">{{ $agendamento->tema }}</span>
                        </div>
                        <span class="text-xs text-gray-400">{{ $agendamento->apresentadores->count() }} apresentador(es)</span>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {{-- Avaliação dos apresentadores --}}
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">Avaliação dos apresentadores (nota 0-10)</h4>
                            <form method="POST"
                                  action="{{ route('ant.professor.apresentacoes.avaliar', [$apresentacao->id, $agendamento->id]) }}">
                                @csrf
                                <table class="w-full text-sm">
                                    @foreach($agendamento->apresentadores as $apresentador)
                                        <tr class="border-b border-gray-50">
                                            <td class="py-1 pr-2">{{ $apresentador->aluno->nome }}</td>
                                            <td class="py-1 w-24">
                                                <input type="number" step="0.1" min="0" max="10"
                                                       name="notas[{{ $apresentador->aluno_ra }}]"
                                                       value="{{ $apresentador->nota !== null ? $apresentador->nota : '' }}"
                                                       placeholder="Nota"
                                                       class="w-full rounded border-gray-300 shadow-sm text-sm">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="pb-2">
                                                <input type="text" name="comentarios[{{ $apresentador->aluno_ra }}]"
                                                       value="{{ $apresentador->comentario }}"
                                                       placeholder="Comentário (opcional)"
                                                       class="w-full rounded border-gray-300 shadow-sm text-xs">
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                                <button type="submit"
                                        class="mt-2 bg-green-600 hover:bg-green-700 text-white px-4 py-1.5 rounded text-sm font-semibold">
                                    Salvar avaliação
                                </button>
                            </form>
                        </div>

                        {{-- Estrelas (3 melhores participações) --}}
                        <div>
                            <h4 class="font-semibold text-gray-700 mb-2">⭐ 3 melhores participações da turma</h4>
                            <form method="POST"
                                  action="{{ route('ant.professor.apresentacoes.estrelas', [$apresentacao->id, $agendamento->id]) }}">
                                @csrf
                                <select name="estrelas[]" multiple size="6"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    @foreach($alunos as $aluno)
                                        <option value="{{ $aluno->ra }}"
                                            {{ in_array($aluno->ra, $estrelasAtuais) ? 'selected' : '' }}>
                                            {{ $aluno->nome }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-400 mt-1">Selecione até 3 alunos (podem ser da turma inteira).</p>
                                <button type="submit"
                                        class="mt-2 bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-1.5 rounded text-sm font-semibold">
                                    Salvar estrelas
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center text-gray-500 italic">
                    Nenhum agendamento ainda. Adicione uma data acima para começar o calendário.
                </div>
            @endforelse

            {{-- Rank resumido --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-gray-700">⭐ Rank de Estrelas ({{ $apresentacao->semestre }})</h3>
                    <a href="{{ route('ant.professor.apresentacoes.rank', $apresentacao->materia_id) }}"
                       class="text-indigo-600 text-sm font-semibold">Ver completo →</a>
                </div>
                <ol class="list-decimal list-inside space-y-1 text-sm">
                    @foreach($rank as $item)
                        <li class="{{ $item->estrelas > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                            {{ $item->aluno->nome }}
                            <span class="text-yellow-600 font-bold">{{ $item->estrelas }} ⭐</span>
                        </li>
                    @endforeach
                </ol>
            </div>

        </div>
    </div>
</x-ANT::layout>
