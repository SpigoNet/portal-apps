<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Comando Mágico IA') }} ✨
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    @if(isset($contextLabel))
                        <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 p-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm text-indigo-700 font-bold uppercase tracking-wider">Contexto Ativo</p>
                                <p class="text-lg font-medium text-indigo-900">{{ $contextLabel }}</p>
                            </div>
                            <span class="text-2xl">🎯</span>
                        </div>
                    @endif

                    <h3 class="text-lg font-bold mb-2">O que você deseja fazer?</h3>
                    <p class="text-sm text-gray-500 mb-4">
                        @if(isset($type) && $type == 'project')
                            Ex: "Adicione uma fase 'Revisão' e mova todas as tarefas concluídas para ela."
                        @elseif(isset($type) && $type == 'task')
                            Ex: "Mude a prioridade para Urgente e adicione na descrição que preciso ligar antes."
                        @else
                            Ex: "Crie um projeto novo..."
                        @endif
                    </p>

                    @if($errors->any())
                        <div class="bg-red-100 text-red-700 p-3 rounded mb-4">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form action="{{ route('treetask.ai.preview') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="id" value="{{ $id }}">

                        <textarea name="prompt" rows="6" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Digite seu comando..." required autofocus></textarea>

                        <div class="mt-4 flex justify-between items-center">
                            <a href="{{ url()->previous() }}" class="text-gray-500 hover:text-gray-700 underline text-sm">Voltar</a>
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded shadow transition flex items-center">
                                <span class="mr-2">✨</span> Gerar Ação
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
