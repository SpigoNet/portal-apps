<x-Admin::layout>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Modelos do Provedor: {{ $provedor->nome }}
                </h2>
                <a href="{{ route('admin.ai.provedores.index') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                    <i class="fa-solid fa-arrow-left mr-1"></i> Voltar
                </a>
            </div>
        </div>

        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 shadow-sm rounded-r-lg" role="alert">
                    <p class="font-medium">{{ session('success') }}</p>
                </div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b-2 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                                <th class="py-3 px-4 font-semibold text-sm">Modelo</th>
                                <th class="py-3 px-4 font-semibold text-sm">Capacidades</th>
                                <th class="py-3 px-4 font-semibold text-sm">Status</th>
                                <th class="py-3 px-4 font-semibold text-sm">Padrão Atualmente</th>
                                <th class="py-3 px-4 font-semibold text-sm text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($modelos as $m)
                                <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                    <td class="py-4 px-4">
                                        <div class="font-bold text-gray-800 dark:text-gray-200">{{ $m->nome }}</div>
                                        <div class="text-xs text-gray-500 font-mono">{{ $m->modelo_id_externo }}</div>
                                    </td>
                                    <td class="py-4 px-4">
                                        <div class="flex flex-col gap-1">
                                            <div class="text-xs">
                                                <span class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-600 rounded text-gray-600 dark:text-gray-300 mr-1">IN:</span>
                                                @foreach((array)$m->input_types as $in)
                                                    <span class="inline-block px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900 text-blue-700 dark:text-blue-200 rounded-sm text-[10px] uppercase font-bold mr-0.5">{{ $in }}</span>
                                                @endforeach
                                            </div>
                                            <div class="text-xs">
                                                <span class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-600 rounded text-gray-600 dark:text-gray-300 mr-1">OUT:</span>
                                                @foreach((array)$m->output_types as $out)
                                                    <span class="inline-block px-1.5 py-0.5 bg-purple-50 dark:bg-purple-900 text-purple-700 dark:text-purple-200 rounded-sm text-[10px] uppercase font-bold mr-0.5">{{ $out }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-4">
                                        @if($m->is_active)
                                            <span class="px-2.5 py-0.5 text-xs bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full font-semibold border border-green-200 dark:border-green-800 flex items-center w-fit">
                                                <span class="w-1.5 h-1.5 bg-green-500 rounded-full mr-1.5"></span> Ativo
                                            </span>
                                        @else
                                            <span class="px-2.5 py-0.5 text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 rounded-full font-semibold border border-red-200 dark:border-red-800 flex items-center w-fit">
                                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span> Inativo
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4">
                                        @if($m->e_padrao->count() > 0)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($m->e_padrao as $padrao)
                                                    <span class="inline-block px-2 py-0.5 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 rounded text-[10px] font-bold border border-indigo-200 dark:border-indigo-800">
                                                        {{ strtoupper($padrao->input_type) }} &rarr; {{ strtoupper($padrao->output_type) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Nenhum</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-4 text-right">
                                        <div class="flex justify-end items-center gap-2">
                                            <form action="{{ route('admin.ai.modelos.toggle', $m) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs font-bold py-1.5 px-3 rounded shadow-sm transition {{ $m->is_active ? 'bg-amber-500 hover:bg-amber-600 text-white' : 'bg-emerald-600 hover:bg-emerald-700 text-white' }}">
                                                    <i class="fa-solid {{ $m->is_active ? 'fa-pause' : 'fa-play' }} mr-1"></i>
                                                    {{ $m->is_active ? 'Inativar' : 'Ativar' }}
                                                </button>
                                            </form>

                                            <div class="relative inline-block text-left" x-data="{ open: false }">
                                                <button @click="open = !open" type="button" class="text-xs font-bold py-1.5 px-3 rounded shadow-sm bg-indigo-600 hover:bg-indigo-700 text-white transition flex items-center">
                                                    <i class="fa-solid fa-star mr-1"></i> Padrão <i class="fa-solid fa-chevron-down ml-1.5 text-[10px]"></i>
                                                </button>
                                                <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" class="absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 z-20 overflow-hidden border border-gray-200 dark:border-gray-700">
                                                    <div class="py-1">
                                                        @php($pairs = [])
                                                        @php($inputTypes = (array)($m->input_types ?? []))
                                                        @php($outputTypes = (array)($m->output_types ?? []))

                                                        @foreach($inputTypes as $in)
                                                            @foreach($outputTypes as $out)
                                                                @php($pairs[] = ['in' => $in, 'out' => $out])
                                                            @endforeach
                                                        @endforeach

                                                        @if(count($pairs) > 0)
                                                            <div class="px-4 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-wider bg-gray-50 dark:bg-gray-900">
                                                                Definir como padrão para:
                                                            </div>
                                                            @foreach($pairs as $pair)
                                                                @php($key = $pair['in'] . '->' . $pair['out'])
                                                                @php($isCurrent = isset($padroes[$key]) && $padroes[$key]->ai_modelo_id == $m->id)
                                                                <form action="{{ route('admin.ai.modelos.set-default', $m) }}" method="POST" class="block">
                                                                    @csrf
                                                                    <input type="hidden" name="input_type" value="{{ $pair['in'] }}">
                                                                    <input type="hidden" name="output_type" value="{{ $pair['out'] }}">
                                                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm transition {{ $isCurrent ? 'bg-indigo-50 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-200' : 'text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700' }}">
                                                                        <div class="flex justify-between items-center">
                                                                            <span>{{ strtoupper($pair['in']) }} &rarr; {{ strtoupper($pair['out']) }}</span>
                                                                            @if($isCurrent)
                                                                                <i class="fa-solid fa-check text-indigo-600 dark:text-indigo-400"></i>
                                                                            @endif
                                                                        </div>
                                                                    </button>
                                                                </form>
                                                            @endforeach
                                                        @else
                                                            <div class="px-4 py-2 text-sm text-gray-500 italic">Nenhuma capacidade definida</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            @if($modelos->isEmpty())
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400 italic">
                                        Nenhum modelo sincronizado para este provedor. Clique em "Sincronizar" na lista de provedores.
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-Admin::layout>
