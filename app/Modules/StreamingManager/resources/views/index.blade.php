<x-StreamingManager::layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Meus Streamings') }}
            </h2>
            <a href="{{ route('streaming-manager.create') }}"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                Novo Streaming
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($streamings as $streaming)
                        <div class="border rounded-lg p-4 shadow-sm hover:shadow-md transition">
                            <h3 class="text-lg font-bold text-blue-600">{{ $streaming->name }}</h3>
                            <div class="mt-2 text-sm text-gray-600">
                                <p>Custo: R$ {{ number_format($streaming->monthly_cost, 2, ',', '.') }}</p>
                                <p>Saldo: R$ {{ number_format($streaming->balance, 2, ',', '.') }}</p>
                                <p
                                    class="font-semibold {{ $streaming->daysRemaining > 7 ? 'text-green-600' : 'text-red-600' }}">
                                    Até: {{ $streaming->funds_until }} ({{ $streaming->daysRemaining }} dias)
                                </p>
                            </div>
                            <div class="mt-4 flex space-x-2">
                                <a href="{{ route('streaming-manager.show', $streaming) }}"
                                    class="text-blue-500 hover:underline">Detalhes</a>
                                @if($streaming->user_id === Auth::id())
                                    <a href="{{ route('streaming-manager.edit', $streaming) }}"
                                        class="text-gray-500 hover:underline">Editar</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-StreamingManager::layout>