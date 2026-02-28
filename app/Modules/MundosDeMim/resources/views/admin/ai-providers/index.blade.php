<x-MundosDeMim::layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Gerenciar Provedores de IA
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between mb-6">
                <div class="flex gap-2">
                    <a href="{{ route('mundos-de-mim.admin.ai-providers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded shadow hover:bg-indigo-700">
                        + Novo Provedor
                    </a>
                    <form action="{{ route('mundos-de-mim.admin.ai-providers.sync-pollination') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded shadow hover:bg-orange-700">
                            Sync Pollinations
                        </button>
                    </form>
                    <form action="{{ route('mundos-de-mim.admin.ai-providers.sync-airforce') }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                            Sync AirForce
                        </button>
                    </form>
                </div>
                <a href="{{ route('mundos-de-mim.admin.ai-providers.user-settings') }}" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700">
                    Configurações por Usuário
                </a>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow mb-6 p-4">
                <form method="GET" class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Nome, model ou descrição..." 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Driver</label>
                        <select name="driver" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="pollination" {{ request('driver') === 'pollination' ? 'selected' : '' }}>Pollination</option>
                            <option value="gemini" {{ request('driver') === 'gemini' ? 'selected' : '' }}>Gemini</option>
                            <option value="lm_studio" {{ request('driver') === 'lm_studio' ? 'selected' : '' }}>LM Studio</option>
                        </select>
                    </div>
                    <div class="w-36">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                        <select name="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Imagem</option>
                            <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Vídeo</option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Ativo</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inativo</option>
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pagamento</label>
                        <select name="paid" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todos</option>
                            <option value="paid" {{ request('paid') === 'paid' ? 'selected' : '' }}>Pago</option>
                            <option value="free" {{ request('paid') === 'free' ? 'selected' : '' }}>Grátis</option>
                        </select>
                    </div>
                    <div class="w-40">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ordenar por</label>
                        <select name="sort" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="sort_order" {{ request('sort') === 'sort_order' ? 'selected' : '' }}>Ordem</option>
                            <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>Nome</option>
                            <option value="model" {{ request('sort') === 'model' ? 'selected' : '' }}>Model</option>
                            <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Data criação</option>
                        </select>
                    </div>
                    <div class="w-28">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Direção</label>
                        <select name="dir" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="asc" {{ request('dir') === 'asc' ? 'selected' : '' }}>Cresc.</option>
                            <option value="desc" {{ request('dir') === 'desc' ? 'selected' : '' }}>Decresc.</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded shadow hover:bg-gray-700">
                            Filtrar
                        </button>
                        <a href="{{ route('mundos-de-mim.admin.ai-providers.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded shadow hover:bg-gray-400">
                            Limpar
                        </a>
                    </div>
                </form>
            </div>

            <div class="mb-4 text-sm text-gray-600">
                Mostrando {{ $providers->count() }} provedor(es)
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($providers as $provider)
                    <div class="bg-white rounded-lg shadow p-4 {{ $provider->is_default ? 'ring-2 ring-yellow-400' : '' }} {{ !$provider->is_active ? 'opacity-60' : '' }}">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-bold text-lg text-gray-900">
                                    {{ $provider->name }}
                                    @if($provider->is_default)
                                        <span class="ml-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Padrão
                                        </span>
                                    @endif
                                </h3>
                                <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ $provider->model }}</code>
                                <span class="ml-2 text-xs text-gray-500">{{ $provider->driver }}</span>
                            </div>
                            <div class="flex flex-col gap-1">
                                @if($provider->is_active)
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inativo</span>
                                @endif
                                @if($provider->paid_only)
                                    <span class="px-2 py-0.5 text-xs font-semibold rounded-full bg-red-100 text-red-800">Pago</span>
                                @endif
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 mb-3">{{ $provider->description }}</p>

                        <div class="flex gap-2 mb-3">
                            @if($provider->api_key)
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-emerald-100 text-emerald-800">
                                    🔑 Chave configurada
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-yellow-100 text-yellow-800">
                                    🔑 Sem chave
                                </span>
                            @endif
                            @if($provider->supports_image_input)
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-purple-100 text-purple-800">
                                    <svg class="inline w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"/></svg>
                                    Imagem
                                </span>
                            @endif
                            @if($provider->supports_video_output)
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-pink-100 text-pink-800">
                                    <svg class="inline w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/></svg>
                                    Vídeo
                                </span>
                            @endif
                        </div>

                        @if($provider->pricing)
                            <div class="bg-gray-50 rounded p-2 mb-3">
                                <p class="text-xs font-semibold text-gray-500 mb-1">
                                    Preços 
                                    @if(isset($provider->pricing['currency']))
                                        ({{ $provider->pricing['currency'] }}):
                                    @else:
                                        (Pollen):
                                    @endif
                                </p>
                                <div class="grid grid-cols-2 gap-1 text-xs">
                                    @if(isset($provider->pricing['promptTextTokens']))
                                        <div><span class="text-gray-500">Prompt Texto:</span> {{ $provider->pricing['promptTextTokens'] }}</div>
                                    @endif
                                    @if(isset($provider->pricing['promptImageTokens']))
                                        <div><span class="text-gray-500">Prompt Img:</span> {{ $provider->pricing['promptImageTokens'] }}</div>
                                    @endif
                                    @if(isset($provider->pricing['completionImageTokens']))
                                        <div><span class="text-gray-500">Img Saída:</span> {{ $provider->pricing['completionImageTokens'] }}</div>
                                    @endif
                                    @if(isset($provider->pricing['completionVideoSeconds']))
                                        <div><span class="text-gray-500">Vídeo seg:</span> {{ $provider->pricing['completionVideoSeconds'] }}</div>
                                    @endif
                                    @if(isset($provider->pricing['completionVideoTokens']))
                                        <div><span class="text-gray-500">Vídeo tok:</span> {{ $provider->pricing['completionVideoTokens'] }}</div>
                                    @endif
                                    @if(isset($provider->pricing['completionAudioSeconds']))
                                        <div><span class="text-gray-500">Áudio:</span> {{ $provider->pricing['completionAudioSeconds'] }}</div>
                                    @endif
                                    @if(isset($provider->pricing['pricePerMillionTokens']))
                                        <div><span class="text-gray-500">1M tokens:</span> {{ $provider->pricing['pricePerMillionTokens'] }}</div>
                                    @endif
                                    @if(isset($provider->pricing['multiplier']))
                                        <div><span class="text-gray-500">Multiplicador:</span> {{ $provider->pricing['multiplier'] }}</div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-between items-center pt-2 border-t">
                            <div class="text-xs text-gray-400">Ordem: {{ $provider->sort_order }}</div>
                            <div class="flex gap-2">
                                @if(!$provider->is_default)
                                    <form action="{{ route('mundos-de-mim.admin.ai-providers.set-default', $provider->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs text-blue-600 hover:text-blue-900">
                                            Tornar Padrão
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('mundos-de-mim.admin.ai-providers.edit', $provider->id) }}" class="text-xs text-indigo-600 hover:text-indigo-900">
                                    Editar
                                </a>
                                <form action="{{ route('mundos-de-mim.admin.ai-providers.destroy', $provider->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-900" onclick="return confirm('Tem certeza?')">
                                        Excluir
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-8 text-gray-500">
                        Nenhum provedor de IA encontrado.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-MundosDeMim::layout>
