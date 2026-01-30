<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Revisão da Importação
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <form action="{{ route('mundos-de-mim.admin.importador.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-bold text-lg mb-4 text-gray-700">1. Refinamento do Texto</h3>

                        <div class="mb-4">
                            <p class="text-xs font-bold text-red-500 uppercase">Original</p>
                            <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded mb-2 border border-dashed">
                                {{ $rawPrompt }}
                            </div>

                            <p class="text-xs font-bold text-green-600 uppercase">Sugerido (Com Variáveis)</p>
                            <textarea name="final_prompt" rows="6" class="w-full text-sm rounded border-green-300 bg-green-50 text-gray-800 focus:ring-green-500">{{ $processedPrompt }}</textarea>
                            <p class="text-xs text-gray-400 mt-1">Edite acima se necessário.</p>
                        </div>

                        <div class="mt-6 border-t pt-4">
                            <h4 class="font-bold text-sm mb-2 text-indigo-900">Requisitos Detectados</h4>
                            @if(count($suggestedRequirements) > 0)
                                <div class="space-y-2">
                                    @foreach($suggestedRequirements as $idx => $req)
                                        <div class="flex items-center bg-indigo-50 p-2 rounded border border-indigo-100">
                                            <input type="checkbox" name="requirements[{{ $idx }}][enabled]" value="1" checked class="rounded text-indigo-600 focus:ring-indigo-500">

                                            <input type="hidden" name="requirements[{{ $idx }}][key]" value="{{ $req['key'] }}">
                                            <input type="hidden" name="requirements[{{ $idx }}][value]" value="{{ $req['value'] }}">

                                            <div class="ml-3 text-sm">
                                                <span class="font-bold text-indigo-700">{{ $req['key'] }} = {{ $req['value'] }}</span>
                                                <p class="text-xs text-indigo-400">Motivo: {{ $req['reason'] }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic">Nenhum requisito especial detectado (Prompt Universal).</p>
                            @endif
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-bold text-lg mb-4 text-gray-700">2. Destino (Tema)</h3>

                        <div x-data="{ action: '{{ $suggestedThemeId ? 'existing_theme' : 'new_theme' }}' }">

                            <div class="flex items-center mb-4 cursor-pointer" @click="action = 'existing_theme'">
                                <input type="radio" name="action_type" value="existing_theme" x-model="action" class="text-indigo-600">
                                <span class="ml-2 font-medium text-gray-700">Usar Tema Existente</span>
                            </div>

                            <div x-show="action === 'existing_theme'" class="ml-6 mb-6">
                                <select name="theme_id" class="w-full rounded border-gray-300">
                                    <option value="">Selecione...</option>
                                    @foreach($allThemes as $theme)
                                        <option value="{{ $theme->id }}" {{ $suggestedThemeId == $theme->id ? 'selected' : '' }}>
                                            {{ $theme->name }} ({{ $theme->slug }})
                                        </option>
                                    @endforeach
                                </select>
                                @if($suggestedThemeId)
                                    <p class="text-xs text-green-600 mt-1">✨ Sugerido com base no texto.</p>
                                @endif
                            </div>

                            <hr class="my-4">

                            <div class="flex items-center mb-4 cursor-pointer" @click="action = 'new_theme'">
                                <input type="radio" name="action_type" value="new_theme" x-model="action" class="text-indigo-600">
                                <span class="ml-2 font-medium text-gray-700">Criar Novo Tema</span>
                            </div>

                            <div x-show="action === 'new_theme'" class="ml-6">
                                <label class="block text-xs font-bold text-gray-500 mb-1">Nome do Novo Tema</label>
                                <input type="text" name="new_theme_name" value="{{ $newThemeName }}" class="w-full rounded border-gray-300" placeholder="Ex: Cyberpunk 2077">
                                <p class="text-xs text-gray-400 mt-2">O tema será criado como 'Teen' e não-sazonal por padrão. Você poderá editar detalhes (e imagens) depois.</p>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="mt-8 flex justify-end gap-4">
                    <a href="{{ route('mundos-de-mim.admin.importador.index') }}" class="px-6 py-3 bg-gray-200 text-gray-700 rounded shadow font-bold hover:bg-gray-300">
                        Cancelar
                    </a>
                    <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded shadow-lg font-bold hover:bg-indigo-700 transform hover:scale-105 transition-all">
                        🚀 Importar e Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
